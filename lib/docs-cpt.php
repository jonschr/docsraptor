<?php
/**
 * Registers the Docs Raptor custom post type.
 *
 * @package docsraptor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the Docs Raptor custom post type.
 */
add_action( 'init', 'docsraptor_register_docs_post_types' );
function docsraptor_register_docs_post_types() {

	// Name
	$name_plural   = 'Docs';
	$name_singular = 'Doc';
	$post_type     = 'docs';
	$slug          = 'docs';
	$icon          = 'lightbulb'; // https://developer.wordpress.org/resource/dashicons/
	$supports      = array( 'title', 'editor', 'thumbnail' );

	$labels = array(
		'name'               => $name_plural,
		'singular_name'      => $name_singular,
		'add_new'            => 'Add new',
		'add_new_item'       => 'Add new ' . $name_singular,
		'edit_item'          => 'Edit ' . $name_singular,
		'new_item'           => 'New ' . $name_singular,
		'view_item'          => 'View ' . $name_singular,
		'search_items'       => 'Search ' . $name_plural,
		'not_found'          => 'No ' . $name_plural . ' found',
		'not_found_in_trash' => 'No ' . $name_plural . ' found in trash',
		'parent_item_colon'  => '',
		'menu_name'          => $name_plural,
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable'  => true,
		'exclude_from_search' => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'query_var'           => true,
		'rewrite'             => array( 'slug' => $slug ),
		'capability_type'     => 'post',
		'has_archive'         => false,
		'hierarchical'        => true,
		'menu_position'       => null,
		'menu_icon'           => 'dashicons-' . $icon,
		'show_in_rest'        => true, // Enable Gutenberg.
		'supports'            => $supports,
	);

	register_post_type( $post_type, $args );
}

/**
 * Includes docs in the classic editor link search dialog.
 *
 * @param array $query The link query arguments.
 * @return array
 */
add_filter( 'wp_link_query_args', 'docsraptor_include_docs_in_link_query' );
function docsraptor_include_docs_in_link_query( $query ) {
	if ( empty( $query['post_type'] ) ) {
		$query['post_type'] = array( 'post', 'page', 'docs' );

		return $query;
	}

	$post_types = (array) $query['post_type'];

	if ( ! in_array( 'docs', $post_types, true ) ) {
		$post_types[] = 'docs';
	}

	$query['post_type'] = $post_types;

	return $query;
}

/**
 * Includes docs in the block editor link search REST endpoint.
 *
 * @param array           $query_args The REST search query arguments.
 * @param WP_REST_Request $request    The REST request.
 * @return array
 */
add_filter( 'rest_post_search_query', 'docsraptor_include_docs_in_rest_link_search', 10, 2 );
function docsraptor_include_docs_in_rest_link_search( $query_args, $request ) {
	if ( 'post' !== $request->get_param( 'type' ) ) {
		return $query_args;
	}

	if ( empty( $query_args['post_type'] ) ) {
		$query_args['post_type'] = array( 'post', 'page', 'docs' );

		return $query_args;
	}

	$post_types = (array) $query_args['post_type'];

	if ( ! in_array( 'docs', $post_types, true ) ) {
		$post_types[] = 'docs';
	}

	$query_args['post_type'] = $post_types;

	return $query_args;
}
