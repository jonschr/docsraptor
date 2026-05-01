(function (blocks, element, blockEditor) {
	const { registerBlockType } = blocks;
	const { createElement: el } = element;
	const { InnerBlocks, useBlockProps } = blockEditor;

	const TEMPLATE = [
		['core/query-title', { type: 'archive', showPrefix: false }],
		['core/term-description', { className: 'docs-description' }],
	];

	registerBlockType('docsraptor/taxonomy-docs-content', {
		edit: function () {
			const blockProps = useBlockProps({
				className: 'docsraptor-taxonomy-docs-content',
			});

			return el(
				'div',
				blockProps,
				el(
					'div',
					{ className: 'docs-container docsraptor-taxonomy-docs-content-editor' },
					el(
						'div',
						{ className: 'docs-sidebar docs-sidebar-desktop' },
						el('div', { className: 'docs-sidebar-content' })
					),
					el(
						'div',
						{ className: 'docs-main' },
						el(InnerBlocks, {
							template: TEMPLATE,
							templateLock: false,
						}),
						el(
							'div',
							{ className: 'docs-content docsraptor-taxonomy-listing-placeholder' },
							el('p', {}, 'Docs category listing renders here.')
						)
					),
					el('div', { className: 'docs-toc-sidebar' })
				)
			);
		},
		save: function () {
			return el(InnerBlocks.Content);
		},
	});
})(window.wp.blocks, window.wp.element, window.wp.blockEditor);
