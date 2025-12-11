document.addEventListener('DOMContentLoaded', function () {
	// Set search input placeholder with OS-specific keys
	const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
	const keyCombo = isMac ? '⌘K' : 'Ctrl+K';
	const searchInput = document.querySelector('.resources-search-input');
	if (searchInput) {
		searchInput.placeholder = `Search... (${keyCombo})`;
	}

	// Accordion functionality for sidebar
	const toggles = document.querySelectorAll('.resource-type-toggle');
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
	const modal = document.getElementById('resources-search-modal');
	const modalInput = document.getElementById('resources-modal-search');
	const sidebarInput = document.querySelector('.resources-search-input');
	const modalSuggestions = document.querySelector(
		'.resources-search-suggestions-modal'
	);
	let currentSuggestionIndex = -1;

	function showModal() {
		modal.classList.add('show');
		modalInput.focus();
		currentSuggestionIndex = -1;
	}

	function hideModal() {
		modal.classList.remove('show');
		modalSuggestions.style.display = 'none';
		modalInput.value = '';
		currentSuggestionIndex = -1;
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
			modalSuggestions.style.display = 'none';
			currentSuggestionIndex = -1;
			return;
		}
		fetchSuggestionsModal(query);
	});

	// Keyboard navigation
	modalInput.addEventListener('keydown', function (e) {
		const suggestions = document.querySelectorAll(
			'.resources-search-suggestions-modal a'
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

	function fetchSuggestionsModal(query) {
		const url =
			'/wp-json/wp/v2/resources?search=' +
			encodeURIComponent(query) +
			'&per_page=10';
		fetch(url)
			.then((response) => response.json())
			.then((data) => {
				if (data.length > 0) {
					let html = '<ul>';
					data.forEach((post) => {
						html +=
							'<li><a href="' +
							post.link +
							'">' +
							post.title.rendered +
							'</a></li>';
					});
					html += '</ul>';
					modalSuggestions.innerHTML = html;
					modalSuggestions.style.display = 'block';
					currentSuggestionIndex = -1;
					updateSuggestionHighlight(
						document.querySelectorAll(
							'.resources-search-suggestions-modal a'
						)
					);
				} else {
					modalSuggestions.style.display = 'none';
					currentSuggestionIndex = -1;
				}
			})
			.catch((error) => {
				console.error('Error fetching suggestions:', error);
				modalSuggestions.style.display = 'none';
				currentSuggestionIndex = -1;
			});
	}

	// Click outside to close
	modal.addEventListener('click', function (e) {
		if (e.target === modal) {
			hideModal();
		}
	});

	// Generate TOC
	const content = document.querySelector('.resources-content');
	const headings = content.querySelectorAll('h2, h3');
	const tocContent = document.querySelector('.resources-toc-list');

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
