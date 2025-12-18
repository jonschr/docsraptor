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
 * Get all terms hierarchically organized.
 *
 * @param string $taxonomy The taxonomy to get terms from.
 * @return array Organized terms with children.
 */
function docsraptor_get_terms_hierarchical( $taxonomy ) {
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
		usort( $organized_terms, function( $a, $b ) {
			$a_order = isset( $a->term_order ) ? $a->term_order : 0;
			$b_order = isset( $b->term_order ) ? $b->term_order : 0;
			return $a_order - $b_order;
		} );

		foreach ( $organized_terms as $term ) {
			if ( isset( $term->children ) ) {
				usort( $term->children, function( $a, $b ) {
					$a_order = isset( $a->term_order ) ? $a->term_order : 0;
					$b_order = isset( $b->term_order ) ? $b->term_order : 0;
					return $a_order - $b_order;
				} );
			}
		}

		// Filter out empty terms (no posts and no children with posts).
		$organized_terms = array_filter( $organized_terms, 'docsraptor_term_has_content' );
	}

	return $organized_terms;
}

/**
 * Check if a term has posts or children with posts.
 *
 * @param object $term The term object.
 * @return bool Whether the term has content.
 */
function docsraptor_term_has_content( $term ) {
	$args = array(
		'post_type'      => 'docs',
		'tax_query'      => array(
			array(
				'taxonomy'         => 'docs-categories',
				'field'            => 'term_id',
				'terms'            => $term->term_id,
				'include_children' => false,
			),
		),
		'posts_per_page' => 1,
		'fields'         => 'ids',
	);
	$posts = get_posts( $args );

	if ( ! empty( $posts ) ) {
		return true;
	}

	if ( isset( $term->children ) && ! empty( $term->children ) ) {
		$term->children = array_filter( $term->children, 'docsraptor_term_has_content' );
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
 * @param int      $level           The current nesting level.
 */
function docsraptor_display_terms_hierarchy( $terms, $current_post_id = null, $deepest_term_id = null, $current_term_id = null, $level = 0 ) {
	// Return early if no terms.
	if ( empty( $terms ) ) {
		return;
	}

	if ( 0 === $level ) {
		echo '<ul class="docs-list open">';
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
				array(
					'taxonomy'         => 'docs-categories',
					'field'            => 'term_id',
					'terms'            => $term->term_id,
					'include_children' => false,
				),
			),
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		);
		$term_posts  = get_posts( $term_posts_args );
		$has_content = ! empty( $term_posts ) || $has_children;
		?>
		<li class="docs-category-item <?php echo $is_current_term ? 'current' : ''; ?>">
			<div class="docs-category <?php echo $level > 0 ? 'child' : 'parent'; ?> <?php echo $is_current_term ? 'current' : ''; ?> <?php echo $has_content ? 'has-children' : ''; ?>">
				<div class="docs-category-toggle <?php echo ! $has_content ? 'no-toggle' : ''; ?>" role="button" tabindex="0" aria-expanded="<?php echo $is_current_term ? 'true' : 'false'; ?>">
					<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="docs-category-link"><?php echo esc_html( $term->name ); ?></a>
				</div>
				<?php if ( $has_content ) : ?>
					<ul class="docs-list <?php echo $is_current_term ? 'open' : ''; ?>">
						<?php foreach ( $term_posts as $post_item ) : ?>
							<?php
							$is_current_post = false;
							if ( $current_post_id ) {
								$is_current_post = ( $current_post_id === $post_item->ID && $term->term_id === $deepest_term_id );
							}
							?>
							<li class="docs-post <?php echo $is_current_post ? 'current' : ''; ?>">
								<a href="<?php echo get_permalink( $post_item->ID ); ?>">
									<?php echo esc_html( $post_item->post_title ); ?>
								</a>
							</li>
						<?php endforeach; ?>
						<?php
						if ( $has_children ) {
							docsraptor_display_terms_hierarchy( $term->children, $current_post_id, $deepest_term_id, $current_term_id, $level + 1 );
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
 */
function docsraptor_output_sidebar( $current_post_id = null, $current_term_id = null ) {
	// Get uncategorized posts.
	$uncategorized_args = array(
		'post_type'      => 'docs',
		'tax_query'      => array(
			array(
				'taxonomy' => 'docs-categories',
				'operator' => 'NOT EXISTS',
			),
		),
		'posts_per_page' => -1,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	);
	$uncategorized_posts = get_posts( $uncategorized_args );

	if ( ! empty( $uncategorized_posts ) ) :
		foreach ( $uncategorized_posts as $post_item ) :
			$current = '';
			if ( $current_post_id && $current_post_id === $post_item->ID ) {
				$current = 'current';
			}
			?>
			<div class="docs-type uncategorized-post <?php echo esc_attr( $current ); ?>">
				<a href="<?php echo esc_url( get_permalink( $post_item->ID ) ); ?>">
					<?php echo esc_html( $post_item->post_title ); ?>
				</a>
			</div>
			<?php
		endforeach;
	endif;

	$terms = docsraptor_get_terms_hierarchical( 'docs-categories' );

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
		docsraptor_display_terms_hierarchy( $terms, $current_post_id, $deepest_term_id, $current_term_id );
	endif;
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
 * Output the search modal.
 */
function docsraptor_output_search_modal() {
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
	// First check for uncategorized posts.
	$uncategorized_args = array(
		'post_type'      => 'docs',
		'tax_query'      => array(
			array(
				'taxonomy' => 'docs-categories',
				'operator' => 'NOT EXISTS',
			),
		),
		'posts_per_page' => 1,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	);
	$uncategorized_posts = get_posts( $uncategorized_args );

	if ( ! empty( $uncategorized_posts ) ) {
		return get_permalink( $uncategorized_posts[0]->ID );
	}

	// Then check for first category with content (by term_order).
	$terms = get_terms( array(
		'taxonomy'   => 'docs-categories',
		'hide_empty' => false,
		'parent'     => 0,
		'orderby'    => 'term_order',
		'order'      => 'ASC',
	) );
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			// Check if this term has any posts (including in children).
			$posts_in_term = get_posts( array(
				'post_type'      => 'docs',
				'tax_query'      => array(
					array(
						'taxonomy'         => 'docs-categories',
						'field'            => 'term_id',
						'terms'            => $term->term_id,
						'include_children' => true,
					),
				),
				'posts_per_page' => 1,
				'fields'         => 'ids',
			) );
			if ( ! empty( $posts_in_term ) ) {
				$term_link = get_term_link( $term );
				if ( ! is_wp_error( $term_link ) ) {
					return $term_link;
				}
			}
		}
	}

	// Fallback: get the very first doc post.
	$first_doc = get_posts( array(
		'post_type'      => 'docs',
		'posts_per_page' => 1,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	) );
	if ( ! empty( $first_doc ) ) {
		return get_permalink( $first_doc[0]->ID );
	}

	// Final fallback if no docs exist at all.
	return home_url( '/' );
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
	?>
	<div class="docs-breadcrumbs">
		<?php docsraptor_output_home_icon(); ?>
		<?php
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
					$ancestor_link = get_term_link( $ancestor );
					if ( ! is_wp_error( $ancestor_link ) ) {
						echo '<a href="' . esc_url( $ancestor_link ) . '">' . esc_html( $ancestor->name ) . '</a><span class="docs-breadcrumb-sep">›</span>';
					}
				}
			}
			?>
			<?php
			$deepest_link = get_term_link( $deepest_term );
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
	?>
	<div class="docs-breadcrumbs">
		<?php docsraptor_output_home_icon(); ?>
		<?php
		$ancestors = get_ancestors( $term->term_id, 'docs-categories' );
		if ( ! empty( $ancestors ) ) :
			$ancestors = array_reverse( $ancestors );
			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'docs-categories' );
				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$ancestor_link = get_term_link( $ancestor );
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
