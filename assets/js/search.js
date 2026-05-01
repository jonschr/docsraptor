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

	let currentSuggestionIndex = -1;
	let allDocs = null;
	let allTerms = null;
	let activeQuery = '';
	const currentCollectionId =
		window.docsraptorSearch && window.docsraptorSearch.collectionId
			? parseInt(window.docsraptorSearch.collectionId, 10)
			: null;

	// Re-enable hover effects when mouse moves over suggestions
	if (modalSuggestions) {
		modalSuggestions.addEventListener('mousemove', function () {
			modalSuggestions.classList.remove('hover-disabled');
		});
	}

	function showModal() {
		modal.classList.add('show');
		modalInput.focus();
		currentSuggestionIndex = -1;
		// Fetch all docs if not already loaded
		if (!allDocs) {
			fetchAllDocs();
		}
	}

	function hideModal() {
		modal.classList.remove('show');
		modalSuggestions.innerHTML = '';
		modalInput.value = '';
		activeQuery = '';
		currentSuggestionIndex = -1;
	}

	function fetchRestPages(url) {
		return fetch(url)
			.then((response) =>
				response.json().then((data) => ({
					data,
					totalPages: parseInt(
						response.headers.get('X-WP-TotalPages') || '1',
						10
					),
				}))
			)
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

	function fetchAllDocs() {
		const docsUrl = '/wp-json/wp/v2/docs?per_page=100&_embed';
		const termsUrl = '/wp-json/wp/v2/docs-categories?per_page=100';
		Promise.all([
			fetchRestPages(docsUrl),
			fetchRestPages(termsUrl),
		])
			.then(([docs, terms]) => {
				allDocs = docs;
				allTerms = terms.reduce((map, term) => {
					map[term.id] = term;
					return map;
				}, {});
				if (activeQuery.length >= 2) {
					filterSuggestions(activeQuery);
				}
			})
			.catch((error) => {
				console.error('Error fetching docs or terms:', error);
			});
	}

	// Keyboard shortcut
	document.addEventListener('keydown', function (e) {
		if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
			e.preventDefault();
			if (modal.classList.contains('show')) {
				hideModal();
			} else {
				showModal();
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
			showModal();
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
			return post['docs-collections'];
		}

		return getPostTerms(post, 'docs-collections').map((term) => term.id);
	}

	function getSearchableTermNames(post) {
		return getPostTerms(post, 'docs-categories')
			.concat(getPostTerms(post, 'docs-collections'))
			.map((term) => term.name)
			.join(' ');
	}

	function filterSuggestions(query) {
		if (!allDocs) {
			return;
		}

		// Disable hover effects until mouse moves
		modalSuggestions.classList.add('hover-disabled');

		// Filter data based on search query
		const normalizedQuery = query.toLowerCase();
		const filteredData = allDocs
			.filter((post) => {
				const postCollections = getPostCollectionIds(post);
				if (currentCollectionId) {
					return postCollections.includes(currentCollectionId);
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
			modalSuggestions.innerHTML = '';
			currentSuggestionIndex = -1;
		}
	}

	// Click outside to close
	modal.addEventListener('click', function (e) {
		if (e.target === modal) {
			hideModal();
		}
	});
});
