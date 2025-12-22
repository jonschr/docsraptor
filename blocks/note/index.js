(function (blocks, element, blockEditor, components) {
	const { registerBlockType, createBlock } = blocks;
	const { createElement: el, Fragment } = element;
	const { useBlockProps, InnerBlocks, InspectorControls, RichText } =
		blockEditor;
	const { ToolbarGroup, ToolbarButton, PanelBody, SelectControl } =
		components;

	// Icon components
	const InfoIcon = () =>
		el(
			'svg',
			{
				xmlns: 'http://www.w3.org/2000/svg',
				fill: 'none',
				viewBox: '0 0 24 24',
				strokeWidth: '1.5',
				stroke: '#004085',
				className: 'size-6',
			},
			el('path', {
				strokeLinecap: 'round',
				strokeLinejoin: 'round',
				d: 'm11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
			})
		);

	const TipIcon = () =>
		el(
			'svg',
			{
				xmlns: 'http://www.w3.org/2000/svg',
				fill: 'none',
				viewBox: '0 0 24 24',
				strokeWidth: '1.5',
				stroke: '#28a745',
				className: 'size-6',
			},
			el('path', {
				strokeLinecap: 'round',
				strokeLinejoin: 'round',
				d: 'M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18',
			})
		);

	const NoteIcon = () =>
		el(
			'svg',
			{
				xmlns: 'http://www.w3.org/2000/svg',
				fill: 'none',
				viewBox: '0 0 24 24',
				strokeWidth: '1.5',
				stroke: 'currentColor',
				className: 'size-6',
			},
			el('path', {
				strokeLinecap: 'round',
				strokeLinejoin: 'round',
				d: 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46',
			})
		);

	const WarningIcon = () =>
		el(
			'svg',
			{
				xmlns: 'http://www.w3.org/2000/svg',
				fill: 'none',
				viewBox: '0 0 24 24',
				strokeWidth: '1.5',
				stroke: 'currentColor',
				className: 'size-6',
			},
			el('path', {
				strokeLinecap: 'round',
				strokeLinejoin: 'round',
				d: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
			})
		);

	const CautionIcon = () =>
		el(
			'svg',
			{
				xmlns: 'http://www.w3.org/2000/svg',
				fill: 'none',
				viewBox: '0 0 24 24',
				strokeWidth: '1.5',
				stroke: 'currentColor',
				className: 'size-6',
			},
			el('path', {
				strokeLinecap: 'round',
				strokeLinejoin: 'round',
				d: 'M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.25-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z',
			})
		);

	const DangerIcon = () =>
		el(
			'svg',
			{
				xmlns: 'http://www.w3.org/2000/svg',
				fill: 'none',
				viewBox: '0 0 24 24',
				strokeWidth: '1.5',
				stroke: 'currentColor',
				className: 'size-6',
			},
			el('path', {
				strokeLinecap: 'round',
				strokeLinejoin: 'round',
				d: 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z',
			})
		);

	const noteTypes = [
		{ label: 'Info', value: 'info', icon: InfoIcon },
		{ label: 'Tip', value: 'tip', icon: TipIcon },
		{ label: 'Note', value: 'note', icon: NoteIcon },
		{ label: 'Warning', value: 'warning', icon: WarningIcon },
		{ label: 'Caution', value: 'caution', icon: CautionIcon },
		{ label: 'Danger', value: 'danger', icon: DangerIcon },
	];

	const getIconElement = (type) => {
		switch (type) {
			case 'info':
				return el(InfoIcon);
			case 'tip':
				return el(TipIcon);
			case 'note':
				return el(NoteIcon);
			case 'warning':
				return el(WarningIcon);
			case 'caution':
				return el(CautionIcon);
			case 'danger':
				return el(DangerIcon);
			default:
				return el(InfoIcon);
		}
	};

	const getBackgroundColor = (type) => {
		switch (type) {
			case 'info':
				return '#e7f3ff';
			case 'tip':
				return '#e6f4ea';
			case 'note':
				return '#f5f5f5';
			case 'warning':
				return '#fff3cd';
			case 'caution':
				return '#fff4e5';
			case 'danger':
				return '#f8d7da';
			default:
				return '#e7f3ff';
		}
	};

	const getTextColor = (type) => {
		switch (type) {
			case 'info':
				return '#004085';
			case 'tip':
				return '#155724';
			case 'note':
				return '#383d41';
			case 'warning':
				return '#856404';
			case 'caution':
				return '#8a4500';
			case 'danger':
				return '#721c24';
			default:
				return '#004085';
		}
	};

	const getDefaultLabel = (type) => {
		switch (type) {
			case 'info':
				return 'Info';
			case 'tip':
				return 'Tip';
			case 'note':
				return 'Note';
			case 'warning':
				return 'Warning';
			case 'caution':
				return 'Caution';
			case 'danger':
				return 'Danger';
			default:
				return 'Info';
		}
	};

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
			const { type, customLabel } = attributes;

			const blockProps = useBlockProps({
				className: 'docsraptor-note docsraptor-note--' + type,
			});

			// Set default label if not set
			const displayLabel = customLabel || getDefaultLabel(type);

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: 'Note Type' },
						el(
							'div',
							{
								style: {
									display: 'flex',
									flexDirection: 'column',
									gap: '8px',
								},
							},
							noteTypes.map(function (noteType) {
								return el(
									'button',
									{
										key: noteType.value,
										onClick: function () {
											setAttributes({
												type: noteType.value,
												customLabel:
													customLabel ||
													getDefaultLabel(
														noteType.value
													),
											});
										},
										style: {
											display: 'flex',
											alignItems: 'center',
											gap: '8px',
											padding: '8px 12px',
											border:
												type === noteType.value
													? '2px solid #007cba'
													: '1px solid #ddd',
											borderRadius: '4px',
											backgroundColor: getBackgroundColor(
												noteType.value
											),
											cursor: 'pointer',
											fontSize: '14px',
											fontWeight: '500',
											color: getTextColor(noteType.value),
											width: '100%',
											textAlign: 'left',
										},
									},
									el(
										'span',
										{
											style: {
												display: 'inline-flex',
												alignItems: 'center',
												justifyContent: 'center',
												width: '20px',
												height: '20px',
											},
										},
										getIconElement(noteType.value)
									),
									el('span', {}, noteType.label)
								);
							})
						)
					)
				),
				el(
					'div',
					blockProps,
					el(
						'div',
						{ className: 'docsraptor-note__header' },
						el('div', { className: 'docsraptor-note__icon' }),
						el(RichText, {
							tagName: 'span',
							className: 'docsraptor-note__label',
							value: displayLabel,
							onChange: function (value) {
								setAttributes({ customLabel: value });
							},
							placeholder: getDefaultLabel(type),
							allowedFormats: [], // No formatting allowed
						})
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
			const { type, customLabel } = attributes;

			const blockProps = useBlockProps.save({
				className: 'docsraptor-note docsraptor-note--' + type,
			});

			// Use custom label or default
			const displayLabel = customLabel || getDefaultLabel(type);

			return el(
				'div',
				blockProps,
				el(
					'div',
					{ className: 'docsraptor-note__header' },
					el('div', { className: 'docsraptor-note__icon' }),
					el(
						'span',
						{ className: 'docsraptor-note__label' },
						displayLabel
					)
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
