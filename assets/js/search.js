/**
 * Search modal functionality
 */
document.addEventListener('DOMContentLoaded', function () {
	// Set search input placeholder with OS-specific keys (hide shortcut on mobile)
	const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
	const keyCombo = isMac ? '⌘K' : 'Ctrl+K';
	const isMobile = window.innerWidth < 768;
	const searchInputs = document.querySelectorAll('.docs-search-input');

	function updatePlaceholders() {
		const showShortcut = window.innerWidth >= 768;
		searchInputs.forEach((input) => {
			input.placeholder = showShortcut
				? `Search... (${keyCombo})`
				: 'Search...';
		});
	}

	updatePlaceholders();
	window.addEventListener('resize', updatePlaceholders);

	// Search modal elements
	const modal = document.getElementById('docs-search-modal');
	const modalInput = document.getElementById('docs-modal-search');
	const modalSuggestions = document.querySelector(
		'.docs-search-suggestions-modal'
	);

	if (!modal || !modalInput || !searchInputs.length) return;

	if (modal.parentElement !== document.body) {
		document.body.appendChild(modal);
	}

	let currentSuggestionIndex = -1;
	let allDocs = null;
	let allDocsFilterKey = null;
	let activeFetchKey = null;
	const docsCache = {};
	let allTerms = null;
	let activeQuery = '';
	let activeFilter = null;
	const currentCollectionId =
		window.docsraptorSearch && window.docsraptorSearch.collectionId
			? parseInt(window.docsraptorSearch.collectionId, 10)
			: null;
	const restUrl =
		window.docsraptorSearch && window.docsraptorSearch.restUrl
			? window.docsraptorSearch.restUrl.replace(/\/$/, '')
			: '/wp-json';

	// Re-enable hover effects when mouse moves over suggestions
	if (modalSuggestions) {
		modalSuggestions.addEventListener('mousemove', function () {
			modalSuggestions.classList.remove('hover-disabled');
		});
	}

	function getTriggerFilter(trigger) {
		const filterSource =
			trigger && trigger.closest
				? trigger.closest(
						'.docs-search-input[data-docsraptor-filter-type], .docs-search-form[data-docsraptor-filter-type], .docsraptor-docs-search[data-docsraptor-filter-type], .wp-block-docsraptor-docs-search[data-docsraptor-filter-type]'
					)
				: trigger;
		const dataset =
			filterSource && filterSource.dataset ? filterSource.dataset : {};
		const filterType = dataset.docsraptorFilterType || 'context';
		const categoryId = parseInt(dataset.docsraptorCategoryId || '0', 10);
		const collectionId = parseInt(
			dataset.docsraptorCollectionId || '0',
			10
		);

		if (filterType === 'category' && categoryId) {
			return {
				type: 'category',
				id: categoryId,
			};
		}

		if (filterType === 'collection' && collectionId) {
			return {
				type: 'collection',
				id: collectionId,
			};
		}

		if (filterType === 'all') {
			return {
				type: 'all',
				id: 0,
			};
		}

		if (filterType === 'category' || filterType === 'collection') {
			return {
				type: 'all',
				id: 0,
			};
		}

		return currentCollectionId
			? {
					type: 'collection',
					id: currentCollectionId,
				}
			: {
					type: 'unassigned',
					id: 0,
				};
	}

	function showModal(trigger) {
		activeFilter = trigger ? getTriggerFilter(trigger) : getTriggerFilter({});
		allDocs = null;
		allDocsFilterKey = null;
		modal.classList.add('show');
		modalInput.focus();
		currentSuggestionIndex = -1;
		fetchDocsForFilter(activeFilter);
	}

	function getDefaultSearchTrigger() {
		return (
			Array.from(searchInputs).find((input) => {
				const rect = input.getBoundingClientRect();
				return rect.width > 0 && rect.height > 0;
			}) || searchInputs[0]
		);
	}

	function hideModal() {
		modal.classList.remove('show');
		modalSuggestions.innerHTML = '';
		modalInput.value = '';
		activeQuery = '';
		activeFilter = null;
		activeFetchKey = null;
		currentSuggestionIndex = -1;
	}

	function fetchRestPages(url) {
		return fetch(url)
			.then((response) => {
				if (!response.ok) {
					throw new Error(
						'Docs search request failed with status ' + response.status
					);
				}

				return response.json().then((data) => ({
					data,
					totalPages: parseInt(
						response.headers.get('X-WP-TotalPages') || '1',
						10
					),
				}));
			})
			.then((firstPage) => {
				if (firstPage.totalPages <= 1) {
					return firstPage.data;
				}

				const pageRequests = [];
				for (let page = 2; page <= firstPage.totalPages; page++) {
					pageRequests.push(
						fetch(url + '&page=' + page).then((response) =>
							response.json()
						)
					);
		}

		return Promise.all(pageRequests).then((pages) =>
			firstPage.data.concat(...pages)
		);
	});
	}

	function renderSearchMessage(message) {
		modalSuggestions.innerHTML =
			'<div class="docs-search-message">' + escapeHtml(message) + '</div>';
		currentSuggestionIndex = -1;
	}

	function getFilterCacheKey(filter) {
		return filter.type + ':' + filter.id;
	}

	function getDocsUrlForFilter(filter) {
		let docsUrl = restUrl + '/docsraptor/v1/search-docs';
		const params = [];

		if (filter.type === 'category' && filter.id) {
			params.push('category_id=' + encodeURIComponent(filter.id));
		}

		if (filter.type === 'collection' && filter.id) {
			params.push('collection_id=' + encodeURIComponent(filter.id));
		}

		if (filter.type === 'unassigned') {
			params.push('unassigned_collection=1');
		}

		return params.length ? docsUrl + '?' + params.join('&') : docsUrl;
	}

	function fetchDocsForFilter(filter) {
		const cacheKey = getFilterCacheKey(filter);
		const docsUrl = getDocsUrlForFilter(filter);
		const termsUrl = restUrl + '/wp/v2/docs-categories?per_page=100';

		if (docsCache[cacheKey] && allTerms) {
			allDocs = docsCache[cacheKey];
			allDocsFilterKey = cacheKey;
			if (activeQuery.length >= 2) {
				filterSuggestions(activeQuery);
			}
			return;
		}

		activeFetchKey = cacheKey;
		Promise.all([
			fetchRestPages(docsUrl),
			allTerms ? Promise.resolve(null) : fetchRestPages(termsUrl),
		])
			.then(([docs, terms]) => {
				if (activeFetchKey !== cacheKey) {
					return;
				}

				allDocs = docs;
				allDocsFilterKey = cacheKey;
				docsCache[cacheKey] = docs;
				if (terms) {
					allTerms = terms.reduce((map, term) => {
						map[term.id] = term;
						return map;
					}, {});
				}
				if (activeQuery.length >= 2) {
					filterSuggestions(activeQuery);
				}
			})
			.catch((error) => {
				console.error('Error fetching docs or terms:', error);
				renderSearchMessage('Search is temporarily unavailable.');
			});
	}

	// Keyboard shortcut
	document.addEventListener('keydown', function (e) {
		if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
			e.preventDefault();
			if (modal.classList.contains('show')) {
				hideModal();
			} else {
				showModal(getDefaultSearchTrigger());
			}
		}
		if (e.key === 'Escape' && modal.classList.contains('show')) {
			hideModal();
		}
	});

	// Click on search inputs to show modal
	searchInputs.forEach((input) => {
		input.addEventListener('click', function (e) {
			e.preventDefault();
			showModal(input);
		});
	});

	// Search in modal
	modalInput.addEventListener('input', function () {
		const query = this.value.trim();
		activeQuery = query;
		if (query.length < 2) {
			modalSuggestions.innerHTML = '';
			currentSuggestionIndex = -1;
			return;
		}
		filterSuggestions(query);
	});

	// Keyboard navigation
	modalInput.addEventListener('keydown', function (e) {
		const suggestions = document.querySelectorAll(
			'.docs-search-suggestions-modal a'
		);
		if (suggestions.length === 0) return;

		if (e.key === 'ArrowDown') {
			e.preventDefault();
			currentSuggestionIndex =
				(currentSuggestionIndex + 1) % suggestions.length;
			updateSuggestionHighlight(suggestions);
		} else if (e.key === 'ArrowUp') {
			e.preventDefault();
			currentSuggestionIndex =
				currentSuggestionIndex <= 0
					? suggestions.length - 1
					: currentSuggestionIndex - 1;
			updateSuggestionHighlight(suggestions);
		} else if (e.key === 'Enter') {
			e.preventDefault();
			if (
				currentSuggestionIndex >= 0 &&
				currentSuggestionIndex < suggestions.length
			) {
				suggestions[currentSuggestionIndex].click();
			}
		}
	});

	function updateSuggestionHighlight(suggestions) {
		suggestions.forEach((s, i) => {
			s.classList.toggle('selected', i === currentSuggestionIndex);
		});
	}

	function getTermPath(post) {
		const postTerms =
			post._embedded && post._embedded['wp:term']
				? post._embedded['wp:term']
						.flat()
						.filter((term) => term.taxonomy === 'docs-categories')
				: [];
		if (postTerms.length === 0) return '';
		// Find deepest term
		let deepest = postTerms[0];
		let maxDepth = 0;
		for (let term of postTerms) {
			let depth = 0;
			let current = term;
			while (current.parent) {
				depth++;
				current = allTerms[current.parent];
				if (!current) break;
			}
			if (depth > maxDepth) {
				maxDepth = depth;
				deepest = term;
			}
		}
		// Build path
		const path = [];
		let current = deepest;
		while (current) {
			path.unshift(current.name);
			current = current.parent ? allTerms[current.parent] : null;
		}
		return path.join(' > ') + ' > ';
	}

	function getSnippet(content, query) {
		const lines = content.split('\n');
		const lowerQuery = query.toLowerCase();
		let matchLineIndex = -1;
		for (let i = 0; i < lines.length; i++) {
			if (lines[i].toLowerCase().includes(lowerQuery)) {
				matchLineIndex = i;
				break;
			}
		}
		if (matchLineIndex === -1) {
			// No match, show first 3 lines joined
			return lines.slice(0, 3).join(' ');
		}
		// Show up to 3 lines around the match, joined
		const start = Math.max(0, matchLineIndex - 1);
		const end = Math.min(lines.length, start + 3);
		return lines.slice(start, end).join(' ');
	}

	function stripHtml(html) {
		const tmp = document.createElement('DIV');
		tmp.innerHTML = html;
		return tmp.textContent || tmp.innerText || '';
	}

	function escapeHtml(text) {
		const div = document.createElement('DIV');
		div.textContent = text;
		return div.innerHTML;
	}

	function escapeAttribute(text) {
		return escapeHtml(text).replace(/"/g, '&quot;');
	}

	function escapeRegExp(text) {
		return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	function highlightText(text, query) {
		const escapedQuery = escapeRegExp(query);
		if (!escapedQuery) {
			return escapeHtml(text);
		}

		const matcher = new RegExp('(' + escapedQuery + ')', 'gi');
		return String(text)
			.split(matcher)
			.map((part) =>
				part.toLowerCase() === query.toLowerCase()
					? '<mark class="search-highlight">' +
						escapeHtml(part) +
						'</mark>'
					: escapeHtml(part)
			)
			.join('');
	}

	function getPostTerms(post, taxonomy) {
		return post._embedded && post._embedded['wp:term']
			? post._embedded['wp:term']
					.flat()
					.filter((term) => term.taxonomy === taxonomy)
			: [];
	}

	function getPostCollectionIds(post) {
		if (Array.isArray(post['docs-collections'])) {
			return post['docs-collections'].map((id) => parseInt(id, 10));
		}

		return getPostTerms(post, 'docs-collections').map((term) =>
			parseInt(term.id, 10)
		);
	}

	function getPostCategoryIds(post) {
		if (Array.isArray(post['docs-categories'])) {
			return post['docs-categories'].map((id) => parseInt(id, 10));
		}

		return getPostTerms(post, 'docs-categories').map((term) =>
			parseInt(term.id, 10)
		);
	}

	function getSearchableTermNames(post) {
		return getPostTerms(post, 'docs-categories')
			.concat(getPostTerms(post, 'docs-collections'))
			.map((term) => term.name)
			.join(' ');
	}

	function filterSuggestions(query) {
		const filter = activeFilter || getTriggerFilter({});
		const filterKey = getFilterCacheKey(filter);

		if (!allDocs || allDocsFilterKey !== filterKey) {
			return;
		}

		// Disable hover effects until mouse moves
		modalSuggestions.classList.add('hover-disabled');

		// Filter data based on search query
		const normalizedQuery = query.toLowerCase();
		const filteredData = allDocs
			.filter((post) => {
				if (filter.type === 'all') {
					return true;
				}

				if (filter.type === 'category') {
					return getPostCategoryIds(post).includes(filter.id);
				}

				const postCollections = getPostCollectionIds(post);
				if (filter.type === 'collection') {
					return postCollections.includes(filter.id);
				}

				return postCollections.length === 0;
			})
			.filter((post) => {
				const searchableText = [
					post.title.rendered,
					stripHtml(post.content.rendered),
					getSearchableTermNames(post),
				]
					.join(' ')
					.toLowerCase();

				return searchableText.includes(normalizedQuery);
			})
			.slice(0, 10); // Limit to 10 results

		if (filteredData.length > 0) {
			let html = '<ul>';
			filteredData.forEach((post) => {
				const path = getTermPath(post);
				const title = stripHtml(post.title.rendered);
				const snippet = getSnippet(
					stripHtml(post.content.rendered),
					query
				);
				html +=
					'<li><a href="' +
					escapeAttribute(post.link) +
					'"><span class="search-path">' +
					highlightText(path, query) +
					'</span><strong>' +
					highlightText(title, query) +
					'</strong><div class="search-snippet">' +
					highlightText(snippet, query) +
					'</div></a></li>';
			});
			html += '</ul>';
			modalSuggestions.innerHTML = html;
			modalSuggestions.querySelector('ul').style.opacity = '1';
			currentSuggestionIndex = -1;
			updateSuggestionHighlight(
				document.querySelectorAll('.docs-search-suggestions-modal a')
			);
		} else {
			renderSearchMessage('No matching docs found.');
		}
	}

	// Click outside to close
	modal.addEventListener('click', function (e) {
		if (e.target === modal) {
			hideModal();
		}
	});
});
