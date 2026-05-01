/**
 * Sidebar accordion functionality
 */
document.addEventListener('DOMContentLoaded', function () {
	const toggles = document.querySelectorAll('.docs-category-toggle');
	const canReorder =
		window.docsraptorSidebar && window.docsraptorSidebar.canReorder;

	// Collapse All button functionality
	const collapseButtons = document.querySelectorAll('.docs-collapse-all');
	collapseButtons.forEach((btn) => {
		btn.addEventListener('click', function () {
			toggles.forEach((toggle) => {
				toggle.setAttribute('aria-expanded', 'false');
				const list = toggle.nextElementSibling;
				if (list) {
					list.classList.remove('open');
				}
			});
		});
	});
	toggles.forEach((toggle) => {
		const handleToggle = function (e) {
			// Don't toggle if clicking the link
			if (
				e.target.closest('.docs-category-link') ||
				e.target.closest('.docs-reorder-handle')
			) {
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

	if (!canReorder) {
		return;
	}

	const sortableGroups = document.querySelectorAll(
		'.docs-sortable-docs, .docs-sortable-categories'
	);
	let dragState = null;

	const getItemType = function (item) {
		if (item.classList.contains('docs-category-item')) {
			return 'category';
		}

		if (item.classList.contains('uncategorized-post')) {
			return 'uncategorized';
		}

		return 'doc';
	};

	const getGroupItems = function (group, itemType) {
		let selector = ':scope > .docs-post[data-doc-id]';

		if (itemType === 'category') {
			selector = ':scope > .docs-category-item[data-term-id]';
		} else if (itemType === 'uncategorized') {
			selector = ':scope > .uncategorized-post[data-doc-id]';
		}

		return Array.from(group.querySelectorAll(selector));
	};

	const getItemIds = function (group, itemType) {
		return getGroupItems(group, itemType).map((item) => {
			return itemType === 'category' ? item.dataset.termId : item.dataset.docId;
		});
	};

	const saveOrder = function (group, itemType) {
		const itemIds = getItemIds(group, itemType);

		if (!itemIds.length) {
			return;
		}

		const formData = new FormData();
		formData.append('action', 'docsraptor_reorder_docs');
		formData.append('nonce', window.docsraptorSidebar.nonce);
		formData.append('orderType', itemType);
		formData.append('categoryId', group.dataset.categoryId || '0');
		formData.append('parentId', group.dataset.parentId || '0');
		formData.append('collectionId', group.dataset.collectionId || '0');
		itemIds.forEach((itemId) => formData.append('itemIds[]', itemId));

		group.classList.add('is-saving');

		fetch(window.docsraptorSidebar.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		})
			.then((response) => {
				if (!response.ok) {
					throw new Error('Unable to save doc order.');
				}
				return response.json();
			})
			.then((result) => {
				if (!result.success) {
					throw new Error('Unable to save doc order.');
				}
				group.classList.add('is-saved');
				window.setTimeout(() => group.classList.remove('is-saved'), 900);
			})
			.catch(() => {
				group.classList.add('has-save-error');
				window.setTimeout(() => group.classList.remove('has-save-error'), 2000);
			})
			.finally(() => {
				group.classList.remove('is-saving');
			});
	};

	const getDragTarget = function (group, itemType, y) {
		return getGroupItems(group, itemType)
			.filter((item) => !item.classList.contains('is-dragging'))
			.reduce(
			(closest, child) => {
				const box = child.getBoundingClientRect();
				const offset = y - box.top - box.height / 2;

				if (offset < 0 && offset > closest.offset) {
					return { offset, element: child };
				}

				return closest;
			},
			{ offset: Number.NEGATIVE_INFINITY, element: null }
		).element;
	};

	const moveItem = function (group, item, itemType, y) {
		const target = getDragTarget(group, itemType, y);

		if (target) {
			group.insertBefore(item, target);
			return;
		}

		if (itemType === 'doc') {
			const firstCategory = group.querySelector(':scope > .docs-category-item');
			if (firstCategory) {
				group.insertBefore(item, firstCategory);
				return;
			}
		}

		group.appendChild(item);
	};

	const finishDrag = function () {
		if (!dragState) {
			return;
		}

		const { group, item, itemType, pointerId, handle } = dragState;
		item.classList.remove('is-dragging');
		document.body.classList.remove('docs-is-reordering');

		if (handle.hasPointerCapture && handle.hasPointerCapture(pointerId)) {
			handle.releasePointerCapture(pointerId);
		}

		dragState = null;
		saveOrder(group, itemType);
	};

	sortableGroups.forEach((group) => {
		group.addEventListener('pointerdown', function (e) {
			const handle = e.target.closest('.docs-reorder-handle');
			if (!handle) {
				return;
			}

			const item = handle.closest(
				'.docs-post[data-doc-id], .uncategorized-post[data-doc-id], .docs-category-item[data-term-id]'
			);

			if (!item || item.parentElement !== group) {
				return;
			}

			const itemType = getItemType(item);

			e.preventDefault();
			e.stopPropagation();

			dragState = {
				group,
				item,
				itemType,
				pointerId: e.pointerId,
				handle,
			};

			item.classList.add('is-dragging');
			document.body.classList.add('docs-is-reordering');
			handle.setPointerCapture(e.pointerId);
		});

		group.addEventListener('pointermove', function (e) {
			if (!dragState || dragState.group !== group) {
				return;
			}

			e.preventDefault();
			moveItem(group, dragState.item, dragState.itemType, e.clientY);
		});

		group.addEventListener('pointerup', finishDrag);
		group.addEventListener('pointercancel', finishDrag);
		group.addEventListener('click', function (e) {
			if (e.target.closest('.docs-reorder-handle')) {
				e.preventDefault();
				e.stopPropagation();
			}
		});
	});
});
