(function (blocks, element, blockEditor, components) {
	const { registerBlockType, createBlock } = blocks;
	const { createElement: el, Fragment } = element;
	const { useBlockProps, InnerBlocks, BlockControls } = blockEditor;
	const { ToolbarGroup, ToolbarButton } = components;

	const noteTypes = [
		{ label: 'Info', value: 'info', icon: 'info' },
		{ label: 'Tip', value: 'tip', icon: 'lightbulb' },
		{ label: 'Note', value: 'note', icon: 'format-aside' },
		{ label: 'Warning', value: 'warning', icon: 'warning' },
		{ label: 'Caution', value: 'caution', icon: 'shield' },
		{ label: 'Danger', value: 'danger', icon: 'dismiss' },
	];

	registerBlockType('docsraptor/note', {
		transforms: {
			from: [
				{
					type: 'block',
					blocks: ['core/paragraph'],
					transform: function (attributes) {
						return createBlock('docsraptor/note', {}, [
							createBlock('core/paragraph', attributes),
						]);
					},
				},
				{
					type: 'block',
					isMultiBlock: true,
					blocks: ['core/paragraph'],
					transform: function (attributes) {
						return createBlock(
							'docsraptor/note',
							{},
							attributes.map(function (attrs) {
								return createBlock('core/paragraph', attrs);
							})
						);
					},
				},
				{
					type: 'block',
					blocks: ['core/heading'],
					transform: function (attributes) {
						return createBlock('docsraptor/note', {}, [
							createBlock('core/paragraph', {
								content: attributes.content,
							}),
						]);
					},
				},
				{
					type: 'block',
					blocks: ['core/list'],
					transform: function (attributes, innerBlocks) {
						return createBlock('docsraptor/note', {}, [
							createBlock('core/list', attributes, innerBlocks),
						]);
					},
				},
			],
			to: [
				{
					type: 'block',
					blocks: ['core/paragraph'],
					transform: function (attributes, innerBlocks) {
						if (innerBlocks.length === 0) {
							return createBlock('core/paragraph');
						}
						if (
							innerBlocks.length === 1 &&
							innerBlocks[0].name === 'core/paragraph'
						) {
							return createBlock(
								'core/paragraph',
								innerBlocks[0].attributes
							);
						}
						return innerBlocks.map(function (block) {
							if (block.name === 'core/paragraph') {
								return createBlock(
									'core/paragraph',
									block.attributes
								);
							}
							return createBlock('core/paragraph', {
								content: '',
							});
						});
					},
				},
			],
		},

		edit: function (props) {
			const { attributes, setAttributes } = props;
			const { type } = attributes;

			const blockProps = useBlockProps({
				className: 'docsraptor-note docsraptor-note--' + type,
			});

			return el(
				Fragment,
				{},
				el(
					BlockControls,
					{},
					el(
						ToolbarGroup,
						{},
						noteTypes.map(function (noteType) {
							return el(ToolbarButton, {
								key: noteType.value,
								icon: noteType.icon,
								label: noteType.label,
								isPressed: type === noteType.value,
								onClick: function () {
									setAttributes({ type: noteType.value });
								},
							});
						})
					)
				),
				el(
					'div',
					blockProps,
					el(
						'div',
						{ className: 'docsraptor-note__header' },
						el('span', { className: 'docsraptor-note__icon' }),
						el('span', { className: 'docsraptor-note__label' })
					),
					el(
						'div',
						{ className: 'docsraptor-note__content' },
						el(InnerBlocks, {
							template: [
								[
									'core/paragraph',
									{
										placeholder:
											'Add your note content here...',
									},
								],
							],
							templateLock: false,
						})
					)
				)
			);
		},

		save: function (props) {
			const { attributes } = props;
			const { type } = attributes;

			const blockProps = useBlockProps.save({
				className: 'docsraptor-note docsraptor-note--' + type,
			});

			return el(
				'div',
				blockProps,
				el(
					'div',
					{ className: 'docsraptor-note__header' },
					el('span', { className: 'docsraptor-note__icon' }),
					el('span', { className: 'docsraptor-note__label' })
				),
				el(
					'div',
					{ className: 'docsraptor-note__content' },
					el(InnerBlocks.Content)
				)
			);
		},
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components
);
