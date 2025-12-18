document.addEventListener('DOMContentLoaded', function () {
	// Set search input placeholder with OS-specific keys
	const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
	const keyCombo = isMac ? '⌘K' : 'Ctrl+K';
	const searchInput = document.querySelector('.docs-search-input');
	if (searchInput) {
		searchInput.placeholder = `Search... (${keyCombo})`;
	}

	// Accordion functionality for sidebar
	const toggles = document.querySelectorAll('.docs-category-toggle');
	toggles.forEach((toggle) => {
		const handleToggle = function (e) {
			// Don't toggle if clicking the link
			if (e.target.closest('.docs-category-link')) {
				return;
			}
			const list = this.nextElementSibling;
			const isOpen = this.getAttribute('aria-expanded') === 'true';
			this.setAttribute('aria-expanded', !isOpen);
			if (list) {
				list.classList.toggle('open');
			}
		};

		toggle.addEventListener('click', handleToggle);
		toggle.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				// Allow Enter on links to navigate
				if (e.target.closest('.docs-category-link')) {
					return;
				}
				e.preventDefault();
				handleToggle.call(this, e);
			}
		});
	});

	// Search modal
	const modal = document.getElementById('docs-search-modal');
	const modalInput = document.getElementById('docs-modal-search');
	const sidebarInput = document.querySelector('.docs-search-input');
	const modalSuggestions = document.querySelector(
		'.docs-search-suggestions-modal'
	);
	let currentSuggestionIndex = -1;
	let allDocs = null;
	let allTerms = null;

	function showModal() {
		modal.classList.add('show');
		console.log('Modal classList:', modal.classList);
		console.log('Modal display:', getComputedStyle(modal).display);
		console.log('Modal offsetHeight:', modal.offsetHeight);
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
				console.log(
					'All docs and terms loaded:',
					docs.length,
					terms.length
				);
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
			console.log('Docs not loaded yet');
			return;
		}
		console.log('Filtering docs for query:', query);
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
		console.log('Filtered data length:', filteredData.length);
		if (filteredData.length > 0) {
			console.log('modalSuggestions element:', modalSuggestions);
			let html = '<ul>';
			filteredData.forEach((post) => {
				console.log('Post title:', post.title.rendered);
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
			modalSuggestions.innerHTML = html;
			modalSuggestions.querySelector('ul').style.opacity = '1';
			console.log('Suggestions HTML set:', html);
			console.log('Suggestions opacity set to 1');
			console.log(
				'Suggestions element outerHTML:',
				modalSuggestions.outerHTML
			);
			console.log(
				'Suggestions offsetHeight:',
				modalSuggestions.offsetHeight
			);
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

	// Helper function to create slug from heading text
	function slugify(text) {
		return text
			.toString()
			.toLowerCase()
			.trim()
			.replace(/\s+/g, '-') // Replace spaces with -
			.replace(/[^\w\-]+/g, '') // Remove non-word chars
			.replace(/\-\-+/g, '-') // Replace multiple - with single -
			.replace(/^-+/, '') // Trim - from start
			.replace(/-+$/, ''); // Trim - from end
	}

	// Track used slugs to avoid duplicates
	const usedSlugs = {};

	function getUniqueSlug(baseSlug) {
		if (!usedSlugs[baseSlug]) {
			usedSlugs[baseSlug] = 1;
			return baseSlug;
		}
		usedSlugs[baseSlug]++;
		return `${baseSlug}-${usedSlugs[baseSlug]}`;
	}

	// Add anchor link to a heading
	function addAnchorLink(heading, id) {
		heading.id = id;
		heading.classList.add('docs-heading-anchor');

		const anchor = document.createElement('a');
		anchor.href = '#' + id;
		anchor.className = 'docs-heading-link';
		anchor.setAttribute('aria-label', 'Link to this section');
		anchor.innerHTML = '#';

		// Fallback copy function for non-HTTPS contexts
		function copyToClipboard(text) {
			if (navigator.clipboard && navigator.clipboard.writeText) {
				return navigator.clipboard.writeText(text);
			}
			// Fallback for HTTP/localhost
			const textarea = document.createElement('textarea');
			textarea.value = text;
			textarea.style.position = 'fixed';
			textarea.style.opacity = '0';
			document.body.appendChild(textarea);
			textarea.select();
			try {
				document.execCommand('copy');
				return Promise.resolve();
			} catch (err) {
				return Promise.reject(err);
			} finally {
				document.body.removeChild(textarea);
			}
		}

		anchor.addEventListener('click', function (e) {
			e.preventDefault();
			const url = window.location.href.split('#')[0] + '#' + id;
			
			// Copy to clipboard
			copyToClipboard(url).then(() => {
				// Show brief "Copied!" feedback
				const originalText = anchor.innerHTML;
				anchor.innerHTML = '✓';
				anchor.classList.add('copied');
				setTimeout(() => {
					anchor.innerHTML = originalText;
					anchor.classList.remove('copied');
				}, 1500);
			}).catch(() => {
				// Silently fail if copy doesn't work
			});

			// Navigate to anchor
			history.pushState(null, null, '#' + id);
			heading.scrollIntoView({ behavior: 'smooth' });
		});

		heading.appendChild(anchor);
	}

	// Generate TOC and add anchor links
	const content = document.querySelector('.docs-content');
	const headings = content ? content.querySelectorAll('h1, h2, h3, h4, h5, h6') : [];
	const tocContent = document.querySelector('.docs-toc-list');

	// Add anchor links to all headings
	headings.forEach((heading) => {
		const baseSlug = slugify(heading.textContent);
		const uniqueSlug = getUniqueSlug(baseSlug);
		addAnchorLink(heading, uniqueSlug);
	});

	// Generate TOC (only h2/h3)
	const tocHeadings = content ? content.querySelectorAll('h2, h3') : [];
	if (tocHeadings.length >= 1 && tocContent) {
		// Show TOC if at least 1 heading
		let tocHtml = '<ul>';
		tocHeadings.forEach((heading) => {
			const id = heading.id;
			const className = heading.tagName === 'H2' ? 'toc-h2' : 'toc-h3';
			tocHtml +=
				'<li class="' +
				className +
				'"><a href="#' +
				id +
				'">' +
				heading.textContent.replace('#', '').trim() +
				'</a></li>';
		});
		tocHtml += '</ul>';
		tocContent.innerHTML = tocHtml;
	}
});
