/**
 * Search modal functionality
 */
document.addEventListener('DOMContentLoaded', function () {
	// Set search input placeholder with OS-specific keys
	const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
	const keyCombo = isMac ? '⌘K' : 'Ctrl+K';
	const searchInput = document.querySelector('.docs-search-input');
	if (searchInput) {
		searchInput.placeholder = `Search... (${keyCombo})`;
	}

	// Search modal elements
	const modal = document.getElementById('docs-search-modal');
	const modalInput = document.getElementById('docs-modal-search');
	const sidebarInput = document.querySelector('.docs-search-input');
	const modalSuggestions = document.querySelector(
		'.docs-search-suggestions-modal'
	);

	if (!modal || !modalInput || !sidebarInput) return;

	let currentSuggestionIndex = -1;
	let allDocs = null;
	let allTerms = null;

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
		currentSuggestionIndex = -1;
	}

	function fetchAllDocs() {
		const docsUrl = '/wp-json/wp/v2/docs?per_page=100&_embed';
		const termsUrl = '/wp-json/wp/v2/docs-categories?per_page=100';
		Promise.all([
			fetch(docsUrl).then((response) => response.json()),
			fetch(termsUrl).then((response) => response.json()),
		])
			.then(([docs, terms]) => {
				allDocs = docs;
				allTerms = terms.reduce((map, term) => {
					map[term.id] = term;
					return map;
				}, {});
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

	// Click on sidebar input to show modal
	sidebarInput.addEventListener('click', function (e) {
		e.preventDefault();
		showModal();
	});

	// Search in modal
	modalInput.addEventListener('input', function () {
		const query = this.value.trim();
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

	function filterSuggestions(query) {
		if (!allDocs) {
			return;
		}

		// Disable hover effects until mouse moves
		modalSuggestions.classList.add('hover-disabled');

		// Filter data based on search query
		const filteredData = allDocs
			.filter(
				(post) =>
					post.title.rendered
						.toLowerCase()
						.includes(query.toLowerCase()) ||
					stripHtml(post.content.rendered)
						.toLowerCase()
						.includes(query.toLowerCase())
			)
			.slice(0, 10); // Limit to 10 results

		if (filteredData.length > 0) {
			let html = '<ul>';
			filteredData.forEach((post) => {
				const path = getTermPath(post);
				const snippet = getSnippet(
					stripHtml(post.content.rendered),
					query
				);
				html +=
					'<li><a href="' +
					post.link +
					'"><span class="search-path">' +
					path +
					'</span><strong>' +
					post.title.rendered +
					'</strong><div class="search-snippet">' +
					snippet +
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
