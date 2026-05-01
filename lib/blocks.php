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
