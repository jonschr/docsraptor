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
