<?php
/**
 * REST search index for Docs Raptor.
 *
 * @package docsraptor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register docs search REST routes.
 */
add_action( 'rest_api_init', 'docsraptor_register_search_rest_routes' );
function docsraptor_register_search_rest_routes() {
	register_rest_route(
		'docsraptor/v1',
		'/search-docs',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'docsraptor_rest_search_docs',
			'permission_callback' => '__return_true',
			'args'                => array(
				'category_id'           => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'default'           => 0,
				),
				'collection_id'         => array(
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'default'           => 0,
				),
				'unassigned_collection' => array(
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
					'default'           => false,
				),
			),
		)
	);
}

/**
 * Return docs search index data.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response
 */
function docsraptor_rest_search_docs( WP_REST_Request $request ) {
	$category_id           = absint( $request->get_param( 'category_id' ) );
	$collection_id         = absint( $request->get_param( 'collection_id' ) );
	$unassigned_collection = (bool) $request->get_param( 'unassigned_collection' );
	$tax_query             = array();

	if ( $category_id ) {
		$tax_query[] = array(
			'taxonomy'         => 'docs-categories',
			'field'            => 'term_id',
			'terms'            => array( $category_id ),
			'include_children' => true,
		);
	}

	if ( $collection_id ) {
		$tax_query[] = array(
			'taxonomy'         => 'docs-collections',
			'field'            => 'term_id',
			'terms'            => array( $collection_id ),
			'include_children' => true,
		);
	} elseif ( $unassigned_collection ) {
		$tax_query[] = array(
			'taxonomy' => 'docs-collections',
			'operator' => 'NOT EXISTS',
		);
	}

	if ( count( $tax_query ) > 1 ) {
		$tax_query['relation'] = 'AND';
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'docs',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => 'menu_order title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'tax_query'              => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		)
	);

	$docs = array();

	foreach ( $query->posts as $post ) {
		$category_terms   = docsraptor_get_rest_search_terms( $post->ID, 'docs-categories' );
		$collection_terms = docsraptor_get_rest_search_terms( $post->ID, 'docs-collections' );

		$docs[] = array(
			'id'                 => $post->ID,
			'link'               => get_permalink( $post ),
			'title'              => array(
				'rendered' => get_the_title( $post ),
			),
			'content'            => array(
				'rendered' => apply_filters( 'the_content', $post->post_content ),
			),
			'docs-categories'    => wp_list_pluck( $category_terms, 'id' ),
			'docs-collections'   => wp_list_pluck( $collection_terms, 'id' ),
			'_embedded'          => array(
				'wp:term' => array(
					$category_terms,
					$collection_terms,
				),
			),
		);
	}

	return rest_ensure_response( $docs );
}

/**
 * Get term data shaped like WP REST embedded terms.
 *
 * @param int    $post_id  Post ID.
 * @param string $taxonomy Taxonomy slug.
 * @return array
 */
function docsraptor_get_rest_search_terms( $post_id, $taxonomy ) {
	$terms = get_the_terms( $post_id, $taxonomy );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array();
	}

	return array_map(
		function ( $term ) use ( $taxonomy ) {
			return array(
				'id'       => (int) $term->term_id,
				'name'     => $term->name,
				'slug'     => $term->slug,
				'taxonomy' => $taxonomy,
				'parent'   => (int) $term->parent,
			);
		},
		array_values( $terms )
	);
}
