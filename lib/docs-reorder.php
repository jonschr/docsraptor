<?php
/**
 * Front-end doc reordering for administrators.
 *
 * @package docsraptor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_docsraptor_reorder_docs', 'docsraptor_ajax_reorder_docs' );
/**
 * Save administrator drag-and-drop order for docs within a category.
 */
function docsraptor_ajax_reorder_docs() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'You are not allowed to reorder docs.', 'docsraptor' ),
			),
			403
		);
	}

	check_ajax_referer( 'docsraptor_reorder_docs', 'nonce' );

	$order_type    = isset( $_POST['orderType'] ) ? sanitize_key( wp_unslash( $_POST['orderType'] ) ) : 'doc';
	$category_id   = isset( $_POST['categoryId'] ) ? absint( $_POST['categoryId'] ) : 0;
	$parent_id     = isset( $_POST['parentId'] ) ? absint( $_POST['parentId'] ) : 0;
	$collection_id = isset( $_POST['collectionId'] ) ? absint( $_POST['collectionId'] ) : 0;
	$item_ids      = isset( $_POST['itemIds'] ) ? array_map( 'absint', (array) $_POST['itemIds'] ) : array();
	$item_ids      = array_values( array_filter( array_unique( $item_ids ) ) );

	if ( empty( $item_ids ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Missing sortable items.', 'docsraptor' ),
			),
			400
		);
	}

	if ( 'category' === $order_type ) {
		$valid_item_ids = get_terms(
			array(
				'taxonomy'   => 'docs-categories',
				'parent'     => $parent_id,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		if ( is_wp_error( $valid_item_ids ) ) {
			$valid_item_ids = array();
		}

		$valid_item_ids = array_map( 'intval', $valid_item_ids );
		$ordered_ids    = array_values( array_intersect( $item_ids, $valid_item_ids ) );

		if ( count( $ordered_ids ) !== count( $valid_item_ids ) ) {
			$ordered_ids = array_values( array_unique( array_merge( $ordered_ids, $valid_item_ids ) ) );
		}

		update_option( docsraptor_get_category_order_option_key( $parent_id, $collection_id ), $ordered_ids, false );

		wp_send_json_success(
			array(
				'itemIds' => $ordered_ids,
			)
		);
	}

	if ( 'uncategorized' !== $order_type && ! $category_id ) {
		wp_send_json_error(
			array(
				'message' => __( 'Missing category.', 'docsraptor' ),
			),
			400
		);
	}

	$doc_tax_query = array(
		'relation' => 'AND',
		docsraptor_get_collection_tax_query_clause( $collection_id ),
	);

	if ( 'uncategorized' === $order_type ) {
		$doc_tax_query[] = array(
			'taxonomy' => 'docs-categories',
			'operator' => 'NOT EXISTS',
		);
	} else {
		$category = get_term( $category_id, 'docs-categories' );
		if ( ! $category || is_wp_error( $category ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid category.', 'docsraptor' ),
				),
				400
			);
		}

		$doc_tax_query[] = array(
			'taxonomy'         => 'docs-categories',
			'field'            => 'term_id',
			'terms'            => $category_id,
			'include_children' => false,
		);
	}

	$valid_doc_ids = get_posts(
		array(
			'post_type'      => 'docs',
			'tax_query'      => $doc_tax_query,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	$valid_doc_ids = array_map( 'intval', $valid_doc_ids );
	$ordered_ids   = array_values( array_intersect( $item_ids, $valid_doc_ids ) );

	if ( count( $ordered_ids ) !== count( $valid_doc_ids ) ) {
		$ordered_ids = array_values( array_unique( array_merge( $ordered_ids, $valid_doc_ids ) ) );
	}

	if ( 'uncategorized' === $order_type ) {
		update_option( docsraptor_get_uncategorized_doc_order_option_key( $collection_id ), $ordered_ids, false );
	} else {
		update_term_meta( $category_id, docsraptor_get_doc_order_meta_key( $collection_id ), $ordered_ids );
	}

	wp_send_json_success(
		array(
			'itemIds' => $ordered_ids,
		)
	);
}
