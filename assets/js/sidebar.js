/**
 * Sidebar accordion functionality
 */
document.addEventListener('DOMContentLoaded', function () {
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
});
