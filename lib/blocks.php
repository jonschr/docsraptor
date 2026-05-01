<?php
/**
 * Register Gutenberg blocks for Docs Raptor
 *
 * @package docsraptor
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Sorry, you are not allowed to access this page directly.' );
}

/**
 * Register all blocks
 */
add_action( 'init', 'docsraptor_register_blocks' );
function docsraptor_register_blocks() {
	// Register the Note block.
	register_block_type( DOCSRAPTOR_DIR . 'blocks/note' );

	// Register the docs search block.
	register_block_type(
		DOCSRAPTOR_DIR . 'blocks/docs-search',
		array(
			'render_callback' => 'docsraptor_render_docs_search_block',
		)
	);

	// Register the dynamic single-doc content block for block themes.
	register_block_type(
		DOCSRAPTOR_DIR . 'blocks/single-doc-content',
		array(
			'render_callback' => 'docsraptor_render_single_doc_content_block',
		)
	);

	// Register the dynamic taxonomy content block for block themes.
	register_block_type(
		DOCSRAPTOR_DIR . 'blocks/taxonomy-docs-content',
		array(
			'render_callback' => 'docsraptor_render_taxonomy_docs_content_block',
		)
	);
}

/**
 * Render the docs search block.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function docsraptor_render_docs_search_block( $attributes = array() ) {
	docsraptor_enqueue_frontend_assets();

	$display_style = isset( $attributes['displayStyle'] ) && 'button' === $attributes['displayStyle'] ? 'button' : 'field';
	$label         = isset( $attributes['label'] ) ? trim( (string) $attributes['label'] ) : '';
	$button_label  = '' !== $label ? $label : __( 'Search docs...', 'docsraptor' );
	$filter_type   = isset( $attributes['filterType'] ) && in_array( $attributes['filterType'], array( 'all', 'category', 'collection' ), true ) ? $attributes['filterType'] : 'all';
	$category_id   = ! empty( $attributes['categoryId'] ) ? absint( $attributes['categoryId'] ) : 0;
	$collection_id = ! empty( $attributes['collectionId'] ) ? absint( $attributes['collectionId'] ) : 0;

	if ( 'category' === $filter_type && ! $category_id ) {
		$filter_type = 'all';
	}

	if ( 'collection' === $filter_type && ! $collection_id ) {
		$filter_type = 'all';
	}

	$trigger_attributes = array(
		'class'                         => 'docs-search-input',
		'data-docsraptor-filter-type'   => $filter_type,
		'data-docsraptor-category-id'   => $category_id,
		'data-docsraptor-collection-id' => $collection_id,
	);

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class'                         => 'docsraptor-docs-search docsraptor-docs-search--' . $display_style,
			'data-docsraptor-filter-type'   => $filter_type,
			'data-docsraptor-category-id'   => $category_id,
			'data-docsraptor-collection-id' => $collection_id,
		)
	);

	ob_start();
	?>
	<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<form
			role="search"
			class="docs-search-form"
			data-docsraptor-filter-type="<?php echo esc_attr( $trigger_attributes['data-docsraptor-filter-type'] ); ?>"
			data-docsraptor-category-id="<?php echo esc_attr( $trigger_attributes['data-docsraptor-category-id'] ); ?>"
			data-docsraptor-collection-id="<?php echo esc_attr( $trigger_attributes['data-docsraptor-collection-id'] ); ?>"
		>
			<?php if ( '' !== $label && 'field' === $display_style ) : ?>
				<label class="docsraptor-docs-search__label">
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endif; ?>
			<?php if ( 'button' === $display_style ) : ?>
				<button
					type="button"
					class="docsraptor-docs-search__button <?php echo esc_attr( $trigger_attributes['class'] ); ?>"
					data-docsraptor-filter-type="<?php echo esc_attr( $trigger_attributes['data-docsraptor-filter-type'] ); ?>"
					data-docsraptor-category-id="<?php echo esc_attr( $trigger_attributes['data-docsraptor-category-id'] ); ?>"
					data-docsraptor-collection-id="<?php echo esc_attr( $trigger_attributes['data-docsraptor-collection-id'] ); ?>"
				>
					<?php echo esc_html( $button_label ); ?>
				</button>
			<?php else : ?>
				<input
					type="search"
					placeholder="<?php echo esc_attr__( 'Search docs...', 'docsraptor' ); ?>"
					class="<?php echo esc_attr( $trigger_attributes['class'] ); ?>"
					data-docsraptor-filter-type="<?php echo esc_attr( $trigger_attributes['data-docsraptor-filter-type'] ); ?>"
					data-docsraptor-category-id="<?php echo esc_attr( $trigger_attributes['data-docsraptor-category-id'] ); ?>"
					data-docsraptor-collection-id="<?php echo esc_attr( $trigger_attributes['data-docsraptor-collection-id'] ); ?>"
					readonly
				/>
			<?php endif; ?>
		</form>
	</div>
	<?php
	docsraptor_require_search_modal();

	return ob_get_clean();
}

/**
 * Render the single-doc content block.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string
 */
function docsraptor_render_single_doc_content_block( $attributes = array(), $content = '', $block = null ) {
	$post_id = 0;
	if ( $block && ! empty( $block->context['postId'] ) ) {
		$post_id = absint( $block->context['postId'] );
	} elseif ( is_singular( 'docs' ) ) {
		$post_id = get_queried_object_id();
	}

	if ( ! $post_id ) {
		return is_admin() ? '<div class="docs-container">' . esc_html__( 'Docs Raptor single doc content renders here.', 'docsraptor' ) . '</div>' : '';
	}

	$post = get_post( $post_id );
	if ( ! $post || 'docs' !== $post->post_type ) {
		return '';
	}

	ob_start();

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'docsraptor-single-doc-content',
		)
	);
	echo '<div ' . $wrapper_attributes . '>';

	$GLOBALS['post'] = $post;
	setup_postdata( $post );
	docsraptor_output_single_doc_content_layout( $content );
	wp_reset_postdata();

	echo '</div>';

	return ob_get_clean();
}

/**
 * Output the single-doc layout.
 *
 * @param string $main_content Optional rendered inner blocks for the main content area.
 * @return void
 */
function docsraptor_output_single_doc_content_layout( $main_content = '' ) {
	require DOCSRAPTOR_DIR . 'templates/parts/single-doc-content.php';
}

/**
 * Render the taxonomy docs content block.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string
 */
function docsraptor_render_taxonomy_docs_content_block( $attributes = array(), $content = '', $block = null ) {
	$term = get_queried_object();

	if ( ! $term || ! isset( $term->term_id ) || ! in_array( $term->taxonomy, array( 'docs-categories', 'docs-collections' ), true ) ) {
		return is_admin() ? '<div class="docs-container">' . esc_html__( 'Docs Raptor taxonomy content renders here.', 'docsraptor' ) . '</div>' : '';
	}

	ob_start();

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'docsraptor-taxonomy-docs-content',
		)
	);
	echo '<div ' . $wrapper_attributes . '>';
	docsraptor_output_taxonomy_docs_content_layout( $content, $term );
	echo '</div>';

	return ob_get_clean();
}

/**
 * Output the taxonomy docs layout.
 *
 * @param string  $main_content Optional rendered inner blocks for the heading/description area.
 * @param WP_Term $term         Optional term object.
 * @return void
 */
function docsraptor_output_taxonomy_docs_content_layout( $main_content = '', $term = null ) {
	require DOCSRAPTOR_DIR . 'templates/parts/taxonomy-docs-content.php';
}

/**
 * Enqueue dashicons on frontend when note block is used
 */
add_action( 'wp_enqueue_scripts', 'docsraptor_enqueue_dashicons' );
function docsraptor_enqueue_dashicons() {
	wp_enqueue_style( 'dashicons' );
}

/**
 * Add custom block category for Docs Raptor blocks
 */
add_filter( 'block_categories_all', 'docsraptor_block_categories', 10, 2 );
function docsraptor_block_categories( $categories, $post ) {
	return array_merge(
		array(
			array(
				'slug'  => 'docsraptor',
				'title' => __( 'Docs Raptor', 'docsraptor' ),
				'icon'  => 'book',
			),
		),
		$categories
	);
}
