<?php
/**
 * Registers the Docs Categories taxonomy.
 *
 * @package docsraptor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the Docs Categories taxonomy.
 */
add_action( 'init', 'docsraptor_register_docs_categories_taxonomy' );
function docsraptor_register_docs_categories_taxonomy() {
	register_taxonomy(
		'docs-categories',
		'docs',
		array(
			'label'        => __( 'Docs Categories' ),
			'rewrite'      => array( 'slug' => 'docs-categories' ),
			'hierarchical' => true,
			'show_in_rest' => true,
			'sort'         => true,
		)
	);
}