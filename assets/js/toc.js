/**
 * Table of Contents generation and heading anchor links
 */
document.addEventListener('DOMContentLoaded', function () {
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

	// Add anchor link to a heading
	function addAnchorLink(heading, id) {
		heading.id = id;
		heading.classList.add('docs-heading-anchor');

		const anchor = document.createElement('a');
		anchor.href = '#' + id;
		anchor.className = 'docs-heading-link';
		anchor.setAttribute('aria-label', 'Link to this section');
		anchor.innerHTML = '#';

		anchor.addEventListener('click', function (e) {
			e.preventDefault();
			const url = window.location.href.split('#')[0] + '#' + id;

			// Copy to clipboard
			copyToClipboard(url)
				.then(() => {
					// Show brief "Copied!" feedback
					const originalText = anchor.innerHTML;
					anchor.innerHTML = '✓';
					anchor.classList.add('copied');
					setTimeout(() => {
						anchor.innerHTML = originalText;
						anchor.classList.remove('copied');
					}, 1500);
				})
				.catch(() => {
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
	const headings = content
		? content.querySelectorAll('h1, h2, h3, h4, h5, h6')
		: [];
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
