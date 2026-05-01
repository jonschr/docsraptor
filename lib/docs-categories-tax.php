<?php
/**
 * Registers the Docs taxonomies.
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

/**
 * Registers the Docs Collections taxonomy.
 */
add_action( 'init', 'docsraptor_register_docs_collections_taxonomy' );
function docsraptor_register_docs_collections_taxonomy() {
	$labels = array(
		'name'              => __( 'Docs Collections', 'docsraptor' ),
		'singular_name'     => __( 'Docs Collection', 'docsraptor' ),
		'search_items'      => __( 'Search Docs Collections', 'docsraptor' ),
		'all_items'         => __( 'All Docs Collections', 'docsraptor' ),
		'parent_item'       => __( 'Parent Docs Collection', 'docsraptor' ),
		'parent_item_colon' => __( 'Parent Docs Collection:', 'docsraptor' ),
		'edit_item'         => __( 'Edit Docs Collection', 'docsraptor' ),
		'update_item'       => __( 'Update Docs Collection', 'docsraptor' ),
		'add_new_item'      => __( 'Add New Docs Collection', 'docsraptor' ),
		'new_item_name'     => __( 'New Docs Collection Name', 'docsraptor' ),
		'menu_name'         => __( 'Docs Collections', 'docsraptor' ),
	);

	register_taxonomy(
		'docs-collections',
		'docs',
		array(
			'labels'       => $labels,
			'rewrite'      => array( 'slug' => 'docs-collections' ),
			'hierarchical' => true,
			'show_in_rest' => true,
			'sort'         => true,
		)
	);
}
