<?php
/**
 * Template output functions for Docs Raptor.
 *
 * @package docsraptor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the active docs collection for the current request.
 *
 * @param int|null $post_id Optional doc ID to inspect first.
 * @return int|null Collection term ID, or null for the uncategorized collection pool.
 */
function docsraptor_get_current_collection_id( $post_id = null ) {
	if ( $post_id ) {
		$post_collections = wp_get_post_terms( $post_id, 'docs-collections', array( 'fields' => 'ids' ) );
		if ( ! empty( $post_collections ) && ! is_wp_error( $post_collections ) ) {
			return (int) $post_collections[0];
		}
	}

	if ( is_tax( 'docs-collections' ) ) {
		$term = get_queried_object();
		if ( $term && isset( $term->term_id ) ) {
			return (int) $term->term_id;
		}
	}

	$collection_slug = get_query_var( 'docs_collection' );
	if ( empty( $collection_slug ) && isset( $_GET['docs_collection'] ) ) {
		$collection_slug = sanitize_title( wp_unslash( $_GET['docs_collection'] ) );
	}

	if ( ! empty( $collection_slug ) ) {
		$collection = get_term_by( 'slug', $collection_slug, 'docs-collections' );
		if ( $collection && ! is_wp_error( $collection ) ) {
			return (int) $collection->term_id;
		}
	}

	return null;
}

/**
 * Build a tax query clause for the active docs collection.
 *
 * @param int|null $collection_id Collection term ID, or null for collectionless docs.
 * @return array Tax query clause.
 */
function docsraptor_get_collection_tax_query_clause( $collection_id = null ) {
	if ( $collection_id ) {
		return array(
			'taxonomy'         => 'docs-collections',
			'field'            => 'term_id',
			'terms'            => $collection_id,
			'include_children' => true,
		);
	}

	return array(
		'taxonomy' => 'docs-collections',
		'operator' => 'NOT EXISTS',
	);
}

/**
 * Get the saved doc order term meta key for a category and collection context.
 *
 * @param int|null $collection_id Active collection ID.
 * @return string Term meta key.
 */
function docsraptor_get_doc_order_meta_key( $collection_id = null ) {
	return '_docsraptor_doc_order_' . (int) $collection_id;
}

/**
 * Get the saved uncategorized doc order option key for a collection context.
 *
 * @param int|null $collection_id Active collection ID.
 * @return string Option key.
 */
function docsraptor_get_uncategorized_doc_order_option_key( $collection_id = null ) {
	return 'docsraptor_uncategorized_doc_order_' . (int) $collection_id;
}

/**
 * Get the saved category order option key for a parent and collection context.
 *
 * @param int      $parent_id     Parent category term ID.
 * @param int|null $collection_id Active collection ID.
 * @return string Option key.
 */
function docsraptor_get_category_order_option_key( $parent_id = 0, $collection_id = null ) {
	return 'docsraptor_category_order_' . (int) $collection_id . '_' . (int) $parent_id;
}

/**
 * Sort docs by the saved front-end order for a category.
 *
 * @param WP_Post[] $posts         Docs to sort.
 * @param int|null  $category_id   Docs category term ID, or null for uncategorized docs.
 * @param int|null  $collection_id Active collection ID.
 * @return WP_Post[] Sorted docs.
 */
function docsraptor_sort_posts_by_saved_order( $posts, $category_id, $collection_id = null ) {
	if ( empty( $posts ) ) {
		return $posts;
	}

	if ( $category_id ) {
		$saved_order = get_term_meta( $category_id, docsraptor_get_doc_order_meta_key( $collection_id ), true );
	} else {
		$saved_order = get_option( docsraptor_get_uncategorized_doc_order_option_key( $collection_id ), array() );
	}

	if ( empty( $saved_order ) || ! is_array( $saved_order ) ) {
		return $posts;
	}

	$order_index = array_flip( array_map( 'intval', $saved_order ) );
	$base_index  = array();

	foreach ( $posts as $index => $post ) {
		$base_index[ $post->ID ] = $index;
	}

	usort(
		$posts,
		function ( $a, $b ) use ( $order_index, $base_index ) {
			$a_has_order = isset( $order_index[ $a->ID ] );
			$b_has_order = isset( $order_index[ $b->ID ] );

			if ( $a_has_order && $b_has_order ) {
				return $order_index[ $a->ID ] - $order_index[ $b->ID ];
			}

			if ( $a_has_order ) {
				return -1;
			}

			if ( $b_has_order ) {
				return 1;
			}

			return $base_index[ $a->ID ] - $base_index[ $b->ID ];
		}
	);

	return $posts;
}

/**
 * Sort sibling category terms by saved front-end order.
 *
 * @param WP_Term[] $terms         Category terms to sort.
 * @param int       $parent_id     Parent category term ID.
 * @param int|null  $collection_id Active collection ID.
 * @return WP_Term[] Sorted category terms.
 */
function docsraptor_sort_terms_by_saved_order( $terms, $parent_id = 0, $collection_id = null ) {
	if ( empty( $terms ) ) {
		return $terms;
	}

	$saved_order = get_option( docsraptor_get_category_order_option_key( $parent_id, $collection_id ), array() );
	if ( empty( $saved_order ) || ! is_array( $saved_order ) ) {
		return $terms;
	}

	$order_index = array_flip( array_map( 'intval', $saved_order ) );
	$base_index  = array();

	foreach ( $terms as $index => $term ) {
		$base_index[ $term->term_id ] = $index;
	}

	usort(
		$terms,
		function ( $a, $b ) use ( $order_index, $base_index ) {
			$a_has_order = isset( $order_index[ $a->term_id ] );
			$b_has_order = isset( $order_index[ $b->term_id ] );

			if ( $a_has_order && $b_has_order ) {
				return $order_index[ $a->term_id ] - $order_index[ $b->term_id ];
			}

			if ( $a_has_order ) {
				return -1;
			}

			if ( $b_has_order ) {
				return 1;
			}

			return $base_index[ $a->term_id ] - $base_index[ $b->term_id ];
		}
	);

	return $terms;
}

/**
 * Sort each nested category branch by term order, then saved front-end order.
 *
 * @param WP_Term[] $terms         Category terms to sort.
 * @param int|null  $collection_id Active collection ID.
 * @return WP_Term[] Sorted category terms.
 */
function docsraptor_sort_terms_tree( $terms, $collection_id = null ) {
	if ( empty( $terms ) ) {
		return $terms;
	}

	usort(
		$terms,
		function ( $a, $b ) {
			$a_order = isset( $a->term_order ) ? $a->term_order : 0;
			$b_order = isset( $b->term_order ) ? $b->term_order : 0;
			return $a_order - $b_order;
		}
	);

	$parent_id = isset( $terms[0]->parent ) ? (int) $terms[0]->parent : 0;
	$terms     = docsraptor_sort_terms_by_saved_order( $terms, $parent_id, $collection_id );

	foreach ( $terms as $term ) {
		if ( isset( $term->children ) ) {
			$term->children = docsraptor_sort_terms_tree( $term->children, $collection_id );
		}
	}

	return $terms;
}

/**
 * Add collection context to docs category links.
 *
 * @param WP_Term  $term          The docs category term.
 * @param int|null $collection_id Active collection ID.
 * @return string Term link.
 */
function docsraptor_get_category_link( $term, $collection_id = null ) {
	$link = get_term_link( $term );

	if ( is_wp_error( $link ) ) {
		return $link;
	}

	$single_doc_id = docsraptor_get_single_doc_id_for_category( $term, $collection_id );
	if ( $single_doc_id ) {
		return get_permalink( $single_doc_id );
	}

	if ( ! $collection_id ) {
		return $link;
	}

	$collection = get_term( $collection_id, 'docs-collections' );
	if ( ! $collection || is_wp_error( $collection ) ) {
		return $link;
	}

	return add_query_arg( 'docs_collection', $collection->slug, $link );
}

/**
 * Get the only doc under a category for the active collection context.
 *
 * Includes child categories because category archive pages show child category docs.
 *
 * @param WP_Term|int $term          Docs category term or term ID.
 * @param int|null    $collection_id Active collection ID.
 * @return int Doc ID if exactly one doc exists, otherwise 0.
 */
function docsraptor_get_single_doc_id_for_category( $term, $collection_id = null ) {
	$term_id = $term instanceof WP_Term ? (int) $term->term_id : absint( $term );

	if ( ! $term_id ) {
		return 0;
	}

	$posts = get_posts(
		array(
			'post_type'      => 'docs',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 2,
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy'         => 'docs-categories',
					'field'            => 'term_id',
					'terms'            => $term_id,
					'include_children' => true,
				),
				docsraptor_get_collection_tax_query_clause( $collection_id ),
			),
		)
	);

	return 1 === count( $posts ) ? (int) $posts[0] : 0;
}

/**
 * Redirect docs category archives with exactly one doc to that doc.
 */
add_action( 'template_redirect', 'docsraptor_redirect_single_doc_category_archives' );
function docsraptor_redirect_single_doc_category_archives() {
	if ( is_admin() || ! is_tax( 'docs-categories' ) ) {
		return;
	}

	$term = get_queried_object();
	if ( ! $term || ! isset( $term->term_id ) ) {
		return;
	}

	$single_doc_id = docsraptor_get_single_doc_id_for_category( $term, docsraptor_get_current_collection_id() );
	if ( ! $single_doc_id ) {
		return;
	}

	wp_safe_redirect( get_permalink( $single_doc_id ), 302 );
	exit;
}

/**
 * Get the first doc URL for a collection pool.
 *
 * @param int|null $collection_id Collection term ID, or null for collectionless docs.
 * @return string The first doc URL, or the site home URL if no docs exist.
 */
function docsraptor_get_collection_home_url( $collection_id = null ) {
	$first_doc_id = docsraptor_get_first_sidebar_doc_id( $collection_id );

	if ( $first_doc_id ) {
		return get_permalink( $first_doc_id );
	}

	return home_url( '/' );
}

/**
 * Get the first doc ID as it appears in the sidebar.
 *
 * @param int|null $collection_id Collection term ID, or null for collectionless docs.
 * @return int First sidebar doc ID, or 0 if no docs exist.
 */
function docsraptor_get_first_sidebar_doc_id( $collection_id = null ) {
	$uncategorized_posts = get_posts( array(
		'post_type'      => 'docs',
		'tax_query'      => array(
			'relation' => 'AND',
			array(
				'taxonomy' => 'docs-categories',
				'operator' => 'NOT EXISTS',
			),
			docsraptor_get_collection_tax_query_clause( $collection_id ),
		),
		'posts_per_page' => -1,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	) );
	$uncategorized_posts = docsraptor_sort_posts_by_saved_order( $uncategorized_posts, null, $collection_id );

	if ( ! empty( $uncategorized_posts ) ) {
		return (int) $uncategorized_posts[0]->ID;
	}

	$terms = docsraptor_get_terms_hierarchical( 'docs-categories', $collection_id );

	return docsraptor_get_first_sidebar_doc_id_from_terms( $terms, $collection_id );
}

/**
 * Get the first doc ID from an ordered sidebar category tree.
 *
 * @param WP_Term[] $terms         Ordered docs category terms.
 * @param int|null  $collection_id Active collection ID.
 * @return int First doc ID, or 0 if none found.
 */
function docsraptor_get_first_sidebar_doc_id_from_terms( $terms, $collection_id = null ) {
	if ( empty( $terms ) ) {
		return 0;
	}

	foreach ( $terms as $term ) {
		$term_posts = get_posts( array(
			'post_type'      => 'docs',
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy'         => 'docs-categories',
					'field'            => 'term_id',
					'terms'            => $term->term_id,
					'include_children' => false,
				),
				docsraptor_get_collection_tax_query_clause( $collection_id ),
			),
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		) );
		$term_posts = docsraptor_sort_posts_by_saved_order( $term_posts, $term->term_id, $collection_id );

		if ( ! empty( $term_posts ) ) {
			return (int) $term_posts[0]->ID;
		}

		if ( ! empty( $term->children ) ) {
			$child_doc_id = docsraptor_get_first_sidebar_doc_id_from_terms( $term->children, $collection_id );
			if ( $child_doc_id ) {
				return $child_doc_id;
			}
		}
	}

	return 0;
}

/**
 * Output the active docs collection breadcrumb.
 *
 * @param int|null $collection_id Active collection ID.
 */
function docsraptor_output_collection_breadcrumb( $collection_id = null ) {
	if ( ! $collection_id ) {
		return;
	}

	$collection = get_term( $collection_id, 'docs-collections' );
	if ( ! $collection || is_wp_error( $collection ) ) {
		return;
	}

	$collection_link = docsraptor_get_collection_home_url( $collection_id );

	echo '<a href="' . esc_url( $collection_link ) . '">' . esc_html( $collection->name ) . '</a><span class="docs-breadcrumb-sep">›</span>';
}

/**
 * Get all terms hierarchically organized.
 *
 * @param string $taxonomy The taxonomy to get terms from.
 * @return array Organized terms with children.
 */
function docsraptor_get_terms_hierarchical( $taxonomy, $collection_id = null ) {
	$terms = get_terms( array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'orderby'    => 'term_order',
		'order'      => 'ASC',
	) );

	// Return empty array if no terms or error.
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array();
	}

	$organized_terms = array();
	$terms_by_id     = array();

	foreach ( $terms as $term ) {
		$terms_by_id[ $term->term_id ] = $term;
		if ( 0 === $term->parent ) {
			$organized_terms[] = $term;
		}
	}

	foreach ( $terms as $term ) {
		if ( 0 !== $term->parent && isset( $terms_by_id[ $term->parent ] ) ) {
			if ( ! isset( $terms_by_id[ $term->parent ]->children ) ) {
				$terms_by_id[ $term->parent ]->children = array();
			}
			$terms_by_id[ $term->parent ]->children[] = $term;
		}
	}

	// Sort organized_terms and children by term_order.
	if ( ! empty( $organized_terms ) ) {
		$organized_terms = docsraptor_sort_terms_tree( $organized_terms, $collection_id );

		// Filter out empty terms (no posts and no children with posts).
		$organized_terms = array_filter( $organized_terms, function( $term ) use ( $collection_id ) {
			return docsraptor_term_has_content( $term, $collection_id );
		} );
	}

	return $organized_terms;
}

/**
 * Check if a term has posts or children with posts.
 *
 * @param object $term The term object.
 * @return bool Whether the term has content.
 */
function docsraptor_term_has_content( $term, $collection_id = null ) {
	$args = array(
		'post_type'      => 'docs',
		'tax_query'      => array(
			'relation' => 'AND',
			array(
				'taxonomy'         => 'docs-categories',
				'field'            => 'term_id',
				'terms'            => $term->term_id,
				'include_children' => false,
			),
			docsraptor_get_collection_tax_query_clause( $collection_id ),
		),
		'posts_per_page' => 1,
		'fields'         => 'ids',
	);
	$posts = get_posts( $args );

	if ( ! empty( $posts ) ) {
		return true;
	}

	if ( isset( $term->children ) && ! empty( $term->children ) ) {
		$term->children = array_filter( $term->children, function( $child ) use ( $collection_id ) {
			return docsraptor_term_has_content( $child, $collection_id );
		} );
		if ( ! empty( $term->children ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Display terms hierarchy recursively.
 *
 * @param array    $terms           The terms to display.
 * @param int|null $current_post_id The current post ID (null for taxonomy pages).
 * @param int|null $deepest_term_id The deepest term ID for the current post.
 * @param int|null $current_term_id The current term ID (for taxonomy pages).
 * @param bool     $expand_current  Whether to expand the current term branch.
 * @param int      $level           The current nesting level.
 */
function docsraptor_display_terms_hierarchy( $terms, $current_post_id = null, $deepest_term_id = null, $current_term_id = null, $expand_current = true, $level = 0, $collection_id = null ) {
	// Return early if no terms.
	if ( empty( $terms ) ) {
		return;
	}

	if ( 0 === $level ) {
		$root_sortable_class = current_user_can( 'manage_options' ) ? ' docs-sortable-categories' : '';
		echo '<ul class="docs-list open' . esc_attr( $root_sortable_class ) . '" data-sort-type="category" data-parent-id="0" data-collection-id="' . esc_attr( (int) $collection_id ) . '">';
	}

	foreach ( $terms as $term ) {
		// Determine if this term is "current" (either viewing the term or a post within it).
		$is_current_term = false;

		if ( $current_term_id ) {
			// On a taxonomy page - check if this term or its ancestors match.
			$term_ancestors = get_ancestors( $current_term_id, 'docs-categories' );
			$is_current_term = ( $term->term_id === $current_term_id ) || in_array( $term->term_id, $term_ancestors, true );
		} elseif ( $current_post_id ) {
			// On a single post - check if post is in this term.
			$post_terms = wp_get_post_terms( $current_post_id, 'docs-categories', array( 'fields' => 'ids' ) );
			$all_current_terms = $post_terms;
			foreach ( $post_terms as $term_id ) {
				$ancestors = get_ancestors( $term_id, 'docs-categories' );
				$all_current_terms = array_merge( $all_current_terms, $ancestors );
			}
			$is_current_term = in_array( $term->term_id, $all_current_terms, true );
		}

		$has_children = isset( $term->children ) && ! empty( $term->children );

		// Get posts in this term.
		$term_posts_args = array(
			'post_type'      => 'docs',
			'tax_query'      => array(
				'relation' => 'AND',
				array(
					'taxonomy'         => 'docs-categories',
					'field'            => 'term_id',
					'terms'            => $term->term_id,
					'include_children' => false,
				),
				docsraptor_get_collection_tax_query_clause( $collection_id ),
			),
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		);
		$term_posts  = get_posts( $term_posts_args );
		$term_posts  = docsraptor_sort_posts_by_saved_order( $term_posts, $term->term_id, $collection_id );
		$has_content = ! empty( $term_posts ) || $has_children;
		$can_reorder = current_user_can( 'manage_options' );
		$is_expanded = $is_current_term && $expand_current;
		?>
		<li class="docs-category-item <?php echo $is_current_term ? 'current' : ''; ?>" data-term-id="<?php echo esc_attr( $term->term_id ); ?>">
			<div class="docs-category <?php echo $level > 0 ? 'child' : 'parent'; ?> <?php echo $is_current_term ? 'current' : ''; ?> <?php echo $has_content ? 'has-children' : ''; ?>">
				<div class="docs-category-toggle <?php echo ! $has_content ? 'no-toggle' : ''; ?>" role="button" tabindex="0" aria-expanded="<?php echo $is_expanded ? 'true' : 'false'; ?>">
					<?php if ( $can_reorder ) : ?>
						<button type="button" class="docs-reorder-handle docs-category-reorder-handle" aria-label="<?php echo esc_attr__( 'Reorder category', 'docsraptor' ); ?>">
							<span aria-hidden="true">::</span>
						</button>
					<?php endif; ?>
					<a href="<?php echo esc_url( docsraptor_get_category_link( $term, $collection_id ) ); ?>" class="docs-category-link"><?php echo esc_html( $term->name ); ?></a>
				</div>
				<?php if ( $has_content ) : ?>
					<ul class="docs-list <?php echo $is_expanded ? 'open' : ''; ?> <?php echo $can_reorder ? 'docs-sortable-docs docs-sortable-categories' : ''; ?>" data-sort-type="mixed" data-category-id="<?php echo esc_attr( $term->term_id ); ?>" data-parent-id="<?php echo esc_attr( $term->term_id ); ?>" data-collection-id="<?php echo esc_attr( (int) $collection_id ); ?>">
						<?php foreach ( $term_posts as $post_item ) : ?>
							<?php
							$is_current_post = false;
							if ( $current_post_id ) {
								$is_current_post = ( $current_post_id === $post_item->ID && $term->term_id === $deepest_term_id );
							}
							?>
							<li class="docs-post <?php echo $is_current_post ? 'current' : ''; ?>" data-doc-id="<?php echo esc_attr( $post_item->ID ); ?>">
								<?php if ( $can_reorder ) : ?>
									<button type="button" class="docs-reorder-handle" aria-label="<?php echo esc_attr__( 'Reorder document', 'docsraptor' ); ?>">
										<span aria-hidden="true">::</span>
									</button>
								<?php endif; ?>
								<a href="<?php echo get_permalink( $post_item->ID ); ?>">
									<?php echo esc_html( $post_item->post_title ); ?>
								</a>
							</li>
						<?php endforeach; ?>
						<?php
						if ( $has_children ) {
							docsraptor_display_terms_hierarchy( $term->children, $current_post_id, $deepest_term_id, $current_term_id, $expand_current, $level + 1, $collection_id );
						}
						?>
					</ul>
				<?php endif; ?>
			</div>
		</li>
		<?php
	}

	if ( 0 === $level ) {
		echo '</ul>';
	}
}

/**
 * Output the left sidebar with navigation.
 *
 * @param int|null $current_post_id The current post ID (null for taxonomy pages).
 * @param int|null $current_term_id The current term ID (for taxonomy pages).
 * @param bool     $expand_current  Whether to expand the current term branch.
 */
function docsraptor_output_sidebar( $current_post_id = null, $current_term_id = null, $expand_current = true ) {
	$collection_id = docsraptor_get_current_collection_id( $current_post_id );
	?>
	<button type="button" class="docs-collapse-all" aria-label="Collapse all categories">
		<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"></polyline><polyline points="20 10 14 10 14 4"></polyline><line x1="14" y1="10" x2="21" y2="3"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg>
		Collapse All
	</button>
	<?php
	// Get uncategorized posts.
	$uncategorized_args = array(
		'post_type'      => 'docs',
		'tax_query'      => array(
			'relation' => 'AND',
			array(
				'taxonomy' => 'docs-categories',
				'operator' => 'NOT EXISTS',
			),
			docsraptor_get_collection_tax_query_clause( $collection_id ),
		),
		'posts_per_page' => -1,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	);
	$uncategorized_posts = get_posts( $uncategorized_args );
	$uncategorized_posts = docsraptor_sort_posts_by_saved_order( $uncategorized_posts, null, $collection_id );
	$can_reorder         = current_user_can( 'manage_options' );

	if ( ! empty( $uncategorized_posts ) ) :
		?>
		<div class="docs-uncategorized-list <?php echo $can_reorder ? 'docs-sortable-docs' : ''; ?>" data-sort-type="uncategorized" data-category-id="0" data-collection-id="<?php echo esc_attr( (int) $collection_id ); ?>">
		<?php
		foreach ( $uncategorized_posts as $post_item ) :
			$current = '';
			if ( $current_post_id && $current_post_id === $post_item->ID ) {
				$current = 'current';
			}
			?>
			<div class="docs-type uncategorized-post <?php echo esc_attr( $current ); ?>" data-doc-id="<?php echo esc_attr( $post_item->ID ); ?>">
				<?php if ( $can_reorder ) : ?>
					<button type="button" class="docs-reorder-handle" aria-label="<?php echo esc_attr__( 'Reorder document', 'docsraptor' ); ?>">
						<span aria-hidden="true">::</span>
					</button>
				<?php endif; ?>
				<a href="<?php echo esc_url( get_permalink( $post_item->ID ) ); ?>">
					<?php echo esc_html( $post_item->post_title ); ?>
				</a>
			</div>
			<?php
		endforeach;
		?>
		</div>
		<?php
	endif;

	$terms = docsraptor_get_terms_hierarchical( 'docs-categories', $collection_id );

	// Find the deepest term for the current post to highlight only there.
	$deepest_term_id = null;
	if ( $current_post_id ) {
		$current_post_terms = wp_get_post_terms( $current_post_id, 'docs-categories', array( 'fields' => 'ids' ) );
		$max_depth = -1;
		foreach ( $current_post_terms as $term_id ) {
			$ancestors = get_ancestors( $term_id, 'docs-categories' );
			$depth     = count( $ancestors );
			if ( $depth > $max_depth ) {
				$max_depth       = $depth;
				$deepest_term_id = $term_id;
			}
		}
	}

	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) :
		docsraptor_display_terms_hierarchy( $terms, $current_post_id, $deepest_term_id, $current_term_id, $expand_current, 0, $collection_id );
	endif;
}

/**
 * Output the mobile/tablet search bar (shown above main content at medium widths).
 */
function docsraptor_output_mobile_search() {
	?>
	<div class="docs-mobile-search">
		<form role="search" class="docs-search-form">
			<input type="search" placeholder="Search docs..." class="docs-search-input" readonly />
		</form>
	</div>
	<?php
}

/**
 * Output the right sidebar with TOC and search.
 */
function docsraptor_output_toc_sidebar() {
	?>
	<div class="docs-toc" id="docs-toc">
		<div class="docs-toc-content">
			<form role="search" class="docs-search-form">
				<input type="search" placeholder="Search docs..." class="docs-search-input" readonly />
			</form>
			<div class="docs-toc-list">
				<!-- TOC will be generated by JS -->
			</div>
		</div>
	</div>
	<?php
}

/**
 * Mark the shared search modal as required for footer output.
 */
function docsraptor_require_search_modal() {
	$GLOBALS['docsraptor_search_modal_required'] = true;
}

/**
 * Output the shared search modal in the footer when requested by blocks.
 */
add_action( 'wp_footer', 'docsraptor_output_required_search_modal' );
function docsraptor_output_required_search_modal() {
	if ( empty( $GLOBALS['docsraptor_search_modal_required'] ) ) {
		return;
	}

	docsraptor_output_search_modal();
}

/**
 * Output the search modal.
 */
function docsraptor_output_search_modal() {
	static $has_output = false;

	if ( $has_output ) {
		return;
	}

	$has_output = true;
	?>
	<div id="docs-search-modal" class="docs-search-modal">
		<div class="docs-search-modal-content">
			<input type="search" id="docs-modal-search" placeholder="Search docs..." class="docs-search-modal-input" />
			<div class="docs-search-suggestions-modal"></div>
		</div>
	</div>
	<?php
}

/**
 * Get the docs home URL (first item in the sidebar).
 *
 * @return string The URL to the docs home.
 */
function docsraptor_get_home_url() {
	$collection_id = docsraptor_get_current_collection_id( get_the_ID() );

	if ( is_tax( 'docs-collections' ) ) {
		$term = get_queried_object();
		if ( $term && isset( $term->term_id ) ) {
			$collection_id = (int) $term->term_id;
		}
	}

	return docsraptor_get_collection_home_url( $collection_id );
}

/**
 * Output the home icon for breadcrumbs.
 */
function docsraptor_output_home_icon() {
	$home_url = docsraptor_get_home_url();
	?>
	<a href="<?php echo esc_url( $home_url ); ?>" class="docs-breadcrumbs-home" title="<?php esc_attr_e( 'Docs Home', 'docsraptor' ); ?>">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 92 96" fill="currentColor" width="16" height="16">
			<path d="M90.2886 42.8581L48.6219 1.1914C47.0022 -0.397133 44.4083 -0.397133 42.7886 1.1914L1.12194 42.8581C0.0281916 44.03 -0.299928 45.733 0.288605 47.2278C0.877138 48.7226 2.2678 49.7434 3.87194 49.8581H8.95527V91.5247C8.95527 92.6289 9.39276 93.6914 10.174 94.4726C10.9553 95.2539 12.0177 95.6914 13.1219 95.6914H36.4552V67.7327C36.4552 65.4305 38.3197 63.566 40.6219 63.566H50.7052C51.8094 63.566 52.8719 64.0035 53.6531 64.7848C54.4343 65.566 54.8718 66.6285 54.8718 67.7326V95.65H78.2051C79.3093 95.65 80.3718 95.2125 81.153 94.4312C81.9342 93.65 82.3717 92.5875 82.3717 91.4834V49.8167H87.6635C89.226 49.65 90.5593 48.6136 91.1165 47.1448C91.6686 45.676 91.3511 44.0142 90.2886 42.8581Z"/>
		</svg>
	</a><span class="docs-breadcrumb-sep">›</span>
	<?php
}

/**
 * Output breadcrumbs for a single doc.
 *
 * @param int $post_id The post ID.
 */
function docsraptor_output_breadcrumbs( $post_id ) {
	$collection_id = docsraptor_get_current_collection_id( $post_id );
	?>
	<div class="docs-breadcrumbs">
		<?php docsraptor_output_home_icon(); ?>
		<?php
		docsraptor_output_collection_breadcrumb( $collection_id );

		$post_terms = wp_get_post_terms( $post_id, 'docs-categories' );
		if ( ! empty( $post_terms ) && ! is_wp_error( $post_terms ) ) :
			// Get all ancestors for the deepest term.
			$deepest_term = $post_terms[0];
			$max_depth = 0;
			foreach ( $post_terms as $term ) {
				$ancestors = get_ancestors( $term->term_id, 'docs-categories' );
				if ( count( $ancestors ) > $max_depth ) {
					$max_depth = count( $ancestors );
					$deepest_term = $term;
				}
			}

			$ancestors = get_ancestors( $deepest_term->term_id, 'docs-categories' );
			$ancestors = array_reverse( $ancestors );

			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'docs-categories' );
				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$ancestor_link = docsraptor_get_category_link( $ancestor, $collection_id );
					if ( ! is_wp_error( $ancestor_link ) ) {
						echo '<a href="' . esc_url( $ancestor_link ) . '">' . esc_html( $ancestor->name ) . '</a><span class="docs-breadcrumb-sep">›</span>';
					}
				}
			}
			?>
			<?php
			$deepest_link = docsraptor_get_category_link( $deepest_term, $collection_id );
			if ( ! is_wp_error( $deepest_link ) ) :
			?>
			<a href="<?php echo esc_url( $deepest_link ); ?>"><?php echo esc_html( $deepest_term->name ); ?></a><span class="docs-breadcrumb-sep">›</span>
			<?php endif; ?>
		<?php endif; ?>
		<span><?php echo get_the_title( $post_id ); ?></span>
	</div>
	<?php
}

/**
 * Output breadcrumbs for a taxonomy term.
 *
 * @param object $term The term object.
 */
function docsraptor_output_term_breadcrumbs( $term ) {
	$collection_id = docsraptor_get_current_collection_id();
	$taxonomy      = isset( $term->taxonomy ) ? $term->taxonomy : 'docs-categories';
	?>
	<div class="docs-breadcrumbs">
		<?php docsraptor_output_home_icon(); ?>
		<?php
		if ( 'docs-categories' === $taxonomy ) {
			docsraptor_output_collection_breadcrumb( $collection_id );
		}

		$ancestors = get_ancestors( $term->term_id, $taxonomy );
		if ( ! empty( $ancestors ) ) :
			$ancestors = array_reverse( $ancestors );
			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, $taxonomy );
				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$ancestor_link = 'docs-categories' === $taxonomy ? docsraptor_get_category_link( $ancestor, $collection_id ) : get_term_link( $ancestor );
					if ( ! is_wp_error( $ancestor_link ) ) {
						echo '<a href="' . esc_url( $ancestor_link ) . '">' . esc_html( $ancestor->name ) . '</a><span class="docs-breadcrumb-sep">›</span>';
					}
				}
			}
		endif;
		?>
		<span><?php echo esc_html( $term->name ); ?></span>
	</div>
	<?php
}
