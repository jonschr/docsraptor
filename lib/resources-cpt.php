<?php
/**
 * Registers the Elodin Resources custom post type.
 *
 * @package elodin-resources
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the Elodin Resources custom post type.
 */
add_action( 'init', 'elodin_resources_register_resources_post_types' );
function elodin_resources_register_resources_post_types() {

	// Name
	$name_plural   = 'Resources';
	$name_singular = 'Resource';
	$post_type     = 'resources';
	$slug          = 'resources';
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
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => $slug ),
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => true,
		'menu_position'      => null,
		'menu_icon'          => 'dashicons-' . $icon,
		'show_in_rest'       => true, // Enable Gutenberg.
		'supports'           => $supports,
	);

	register_post_type( $post_type, $args );
}