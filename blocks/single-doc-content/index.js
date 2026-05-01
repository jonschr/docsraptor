(function (blocks, element, blockEditor) {
	const { registerBlockType } = blocks;
	const { createElement: el } = element;
	const { InnerBlocks, useBlockProps } = blockEditor;

	const TEMPLATE = [
		['core/post-title', { level: 1 }],
		[
			'core/post-date',
			{
				displayType: 'modified',
				className: 'docs-meta',
				format: 'F j, Y',
			},
		],
		['core/post-content', { className: 'docs-content' }],
	];

	registerBlockType('docsraptor/single-doc-content', {
		edit: function () {
			const blockProps = useBlockProps({
				className: 'docsraptor-single-doc-content',
			});

			return el(
				'div',
				blockProps,
				el(
					'div',
					{ className: 'docs-container docsraptor-single-doc-content-editor' },
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
						})
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
