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
		const handleToggle = function () {
			const list = this.nextElementSibling;
			const isOpen = this.getAttribute('aria-expanded') === 'true';
			this.setAttribute('aria-expanded', !isOpen);
			list.classList.toggle('open');
		};

		toggle.addEventListener('click', handleToggle);
		toggle.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				handleToggle.call(this);
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
		const url = '/wp-json/wp/v2/docs?per_page=100';
		fetch(url)
			.then((response) => response.json())
			.then((data) => {
				allDocs = data;
				console.log('All docs loaded:', data.length);
			})
			.catch((error) => {
				console.error('Error fetching all docs:', error);
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

	function filterSuggestions(query) {
		if (!allDocs) {
			console.log('Docs not loaded yet');
			return;
		}
		console.log('Filtering docs for query:', query);
		// Filter data based on search query
		const filteredData = allDocs
			.filter((post) =>
				post.title.rendered.toLowerCase().includes(query.toLowerCase())
			)
			.slice(0, 10); // Limit to 10 results
		console.log('Filtered data length:', filteredData.length);
		if (filteredData.length > 0) {
			console.log('modalSuggestions element:', modalSuggestions);
			let html = '<ul>';
			filteredData.forEach((post) => {
				console.log('Post title:', post.title.rendered);
				html +=
					'<li><a href="' +
					post.link +
					'">' +
					post.title.rendered +
					'</a></li>';
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

	// Generate TOC
	const content = document.querySelector('.docs-content');
	const headings = content.querySelectorAll('h2, h3');
	const tocContent = document.querySelector('.docs-toc-list');

	if (headings.length >= 1) {
		// Show TOC if at least 1 heading
		let tocHtml = '<ul>';
		let h2Count = 0;
		headings.forEach((heading, index) => {
			if (heading.tagName === 'H2') {
				h2Count++;
				const id = 'heading-' + index;
				heading.id = id;
				tocHtml +=
					'<li class="toc-h2"><a href="#' +
					id +
					'">' +
					heading.textContent +
					'</a></li>';
			} else if (heading.tagName === 'H3') {
				const id = 'heading-' + index;
				heading.id = id;
				tocHtml +=
					'<li class="toc-h3"><a href="#' +
					id +
					'">' +
					heading.textContent +
					'</a></li>';
			}
		});
		tocHtml += '</ul>';
		tocContent.innerHTML = tocHtml;
	}
});
