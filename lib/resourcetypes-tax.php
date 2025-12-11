<?php
/**
 * Registers the Elodin Resource Types taxonomy.
 *
 * @package elodin-resources
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the Elodin Resource Types taxonomy.
 */
add_action( 'init', 'elodin_resources_register_resource_types_taxonomy' );
function elodin_resources_register_resource_types_taxonomy() {
	register_taxonomy(
		'resourcetypes',
		'resources',
		array(
			'label'        => __( 'Resource types' ),
			'rewrite'      => array( 'slug' => 'resource-types' ),
			'hierarchical' => true,
			'show_in_rest' => true,
			'sort'         => true,
		)
	);
}