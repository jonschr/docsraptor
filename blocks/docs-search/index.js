(function (blocks, element, blockEditor, components, apiFetch) {
	const { registerBlockType } = blocks;
	const { createElement: el, Fragment, useEffect, useState } = element;
	const { InspectorControls, useBlockProps } = blockEditor;
	const {
		PanelBody,
		SelectControl,
		TextControl,
		Placeholder,
		Spinner,
	} = components;

	function termOptions(terms, emptyLabel) {
		return [{ label: emptyLabel, value: 0 }].concat(
			terms.map((term) => ({
				label: term.name,
				value: term.id,
			}))
		);
	}

	function useTerms(taxonomy) {
		const [terms, setTerms] = useState([]);
		const [isLoading, setIsLoading] = useState(true);

		useEffect(() => {
			let isMounted = true;
			setIsLoading(true);

			apiFetch({ path: '/wp/v2/' + taxonomy + '?per_page=100' })
				.then((results) => {
					if (isMounted) {
						setTerms(results || []);
						setIsLoading(false);
					}
				})
				.catch(() => {
					if (isMounted) {
						setTerms([]);
						setIsLoading(false);
					}
				});

			return function () {
				isMounted = false;
			};
		}, [taxonomy]);

		return { terms, isLoading };
	}

	registerBlockType('docsraptor/docs-search', {
		edit: function ({ attributes, setAttributes }) {
			const {
				displayStyle,
				label,
				filterType,
				categoryId,
				collectionId,
			} = attributes;
			const categories = useTerms('docs-categories');
			const collections = useTerms('docs-collections');
			const blockProps = useBlockProps({
				className:
					'docsraptor-docs-search docsraptor-docs-search--' +
					displayStyle,
			});

			const trigger =
				displayStyle === 'button'
					? el(
							'button',
							{
								type: 'button',
								className:
									'docsraptor-docs-search__button docs-search-input',
							},
							label || 'Search docs...'
						)
					: el(
							'div',
							{},
							label &&
								el(
									'label',
									{ className: 'docsraptor-docs-search__label' },
									label
								),
							el('input', {
								type: 'search',
								className: 'docs-search-input',
								placeholder: 'Search docs...',
								readOnly: true,
							})
						);

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: 'Search Settings' },
						el(SelectControl, {
							label: 'Display',
							value: displayStyle,
							options: [
								{ label: 'Search field', value: 'field' },
								{ label: 'Button', value: 'button' },
							],
							onChange: function (value) {
								setAttributes({ displayStyle: value });
							},
						}),
						el(TextControl, {
							label: 'Label',
							value: label,
							onChange: function (value) {
								setAttributes({ label: value });
							},
						}),
						el(SelectControl, {
							label: 'Filter results',
							value: filterType,
							options: [
								{ label: 'All docs', value: 'all' },
								{ label: 'Docs category', value: 'category' },
								{ label: 'Docs collection', value: 'collection' },
							],
							onChange: function (value) {
								setAttributes({ filterType: value });
							},
						}),
						filterType === 'category' &&
							(categories.isLoading
								? el(Spinner)
								: el(SelectControl, {
										label: 'Category',
										value: categoryId,
										options: termOptions(
											categories.terms,
											'Select a category'
										),
										onChange: function (value) {
											setAttributes({
												categoryId: parseInt(value, 10) || 0,
											});
										},
									})),
						filterType === 'collection' &&
							(collections.isLoading
								? el(Spinner)
								: el(SelectControl, {
										label: 'Collection',
										value: collectionId,
										options: termOptions(
											collections.terms,
											'Select a collection'
										),
										onChange: function (value) {
											setAttributes({
												collectionId: parseInt(value, 10) || 0,
											});
										},
									}))
					)
				),
				el(
					'div',
					blockProps,
					el(
						Placeholder,
						{
							icon: 'search',
							label: 'Docs Search',
							instructions:
								filterType === 'all'
									? 'Searches all docs.'
									: 'Searches the selected docs ' + filterType + '.',
						},
						trigger
					)
				)
			);
		},
		save: function () {
			return null;
		},
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.apiFetch
);
