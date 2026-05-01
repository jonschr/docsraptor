<?php
/**
 * Shared content for displaying docs taxonomy archives.
 *
 * @package docsraptor
 */

$term = $term instanceof WP_Term ? $term : get_queried_object();

// Safety check - ensure we have a valid term object.
if ( ! $term || ! isset( $term->term_id ) ) {
	echo '<p>' . esc_html__( 'Docs term not found.', 'docsraptor' ) . '</p>';
	return;
}

$is_collection_archive = isset( $term->taxonomy ) && 'docs-collections' === $term->taxonomy;
$collection_id         = $is_collection_archive ? (int) $term->term_id : docsraptor_get_current_collection_id();
$sidebar_term_id       = $is_collection_archive ? null : $term->term_id;
$category_parent_id    = $is_collection_archive ? 0 : $term->term_id;
?>

<div class="docs-container">
	<!-- Left Sidebar (desktop) -->
	<div class="docs-sidebar docs-sidebar-desktop">
		<div class="docs-sidebar-content">
			<?php docsraptor_output_sidebar( null, $sidebar_term_id ); ?>
		</div>
	</div>

	<!-- Mobile Sidebar (collapsible) -->
	<details class="docs-sidebar-mobile">
		<summary>
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
			Menu
		</summary>
		<div class="docs-sidebar-content">
			<?php docsraptor_output_sidebar( null, $sidebar_term_id ); ?>
		</div>
	</details>

	<!-- Mobile Search -->
	<?php docsraptor_output_mobile_search(); ?>

	<!-- Main Content -->
	<div class="docs-main">
		<?php docsraptor_output_term_breadcrumbs( $term ); ?>

		<?php if ( ! empty( $main_content ) ) : ?>
			<?php echo $main_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php else : ?>
			<h1><?php echo esc_html( $term->name ); ?></h1>

			<?php if ( ! empty( $term->description ) ) : ?>
				<div class="docs-description">
					<?php echo wpautop( esc_html( $term->description ) ); ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<div class="docs-content">
			<?php
			// Get child categories.
			$child_terms = get_terms(
				array(
					'taxonomy'   => 'docs-categories',
					'parent'     => $category_parent_id,
					'hide_empty' => false,
					'orderby'    => 'term_order',
					'order'      => 'ASC',
				)
			);

			if ( is_wp_error( $child_terms ) ) {
				$child_terms = array();
			}

			// Filter out empty child terms.
			$child_terms = array_filter(
				$child_terms,
				function ( $child ) use ( $collection_id ) {
					$posts = get_posts(
						array(
							'post_type'      => 'docs',
							'tax_query'      => array(
								'relation' => 'AND',
								array(
									'taxonomy'         => 'docs-categories',
									'field'            => 'term_id',
									'terms'            => $child->term_id,
									'include_children' => true,
								),
								docsraptor_get_collection_tax_query_clause( $collection_id ),
							),
							'posts_per_page' => 1,
							'fields'         => 'ids',
						)
					);
					return ! empty( $posts );
				}
			);

			// Get posts directly in this category (not in child categories).
			$direct_tax_query = array(
				'relation' => 'AND',
				docsraptor_get_collection_tax_query_clause( $collection_id ),
			);

			if ( $is_collection_archive ) {
				$direct_tax_query[] = array(
					'taxonomy' => 'docs-categories',
					'operator' => 'NOT EXISTS',
				);
			} else {
				$direct_tax_query[] = array(
					'taxonomy'         => 'docs-categories',
					'field'            => 'term_id',
					'terms'            => $term->term_id,
					'include_children' => false,
				);
			}

			$direct_posts = get_posts(
				array(
					'post_type'      => 'docs',
					'tax_query'      => $direct_tax_query,
					'posts_per_page' => -1,
					'orderby'        => 'menu_order title',
					'order'          => 'ASC',
				)
			);
			if ( ! $is_collection_archive ) {
				$direct_posts = docsraptor_sort_posts_by_saved_order( $direct_posts, $term->term_id, $collection_id );
			}

			if ( ! empty( $child_terms ) || ! empty( $direct_posts ) ) :
				?>
				<div class="docs-category-listing">
					<?php if ( ! empty( $direct_posts ) ) : ?>
						<ul class="docs-posts-list">
							<?php foreach ( $direct_posts as $post_item ) : ?>
								<li>
									<a href="<?php echo esc_url( get_permalink( $post_item->ID ) ); ?>">
										<?php echo esc_html( $post_item->post_title ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( ! empty( $child_terms ) ) : ?>
						<?php foreach ( $child_terms as $child_term ) : ?>
							<div class="docs-subcategory">
								<h2>
									<a href="<?php echo esc_url( docsraptor_get_category_link( $child_term, $collection_id ) ); ?>">
										<?php echo esc_html( $child_term->name ); ?>
									</a>
								</h2>
								<?php if ( ! empty( $child_term->description ) ) : ?>
									<p class="docs-subcategory-description"><?php echo esc_html( $child_term->description ); ?></p>
								<?php endif; ?>
								<?php
								// Get posts in this child category.
								$child_posts = get_posts(
									array(
										'post_type'      => 'docs',
										'tax_query'      => array(
											'relation' => 'AND',
											array(
												'taxonomy'         => 'docs-categories',
												'field'            => 'term_id',
												'terms'            => $child_term->term_id,
												'include_children' => false,
											),
											docsraptor_get_collection_tax_query_clause( $collection_id ),
										),
										'posts_per_page' => 5,
										'orderby'        => 'menu_order title',
										'order'          => 'ASC',
									)
								);
								$child_posts = docsraptor_sort_posts_by_saved_order( $child_posts, $child_term->term_id, $collection_id );

								if ( ! empty( $child_posts ) ) :
									?>
									<ul class="docs-posts-list">
										<?php foreach ( $child_posts as $child_post ) : ?>
											<li>
												<a href="<?php echo esc_url( get_permalink( $child_post->ID ) ); ?>">
													<?php echo esc_html( $child_post->post_title ); ?>
												</a>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<p><?php esc_html_e( 'No docs found in this category.', 'docsraptor' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<!-- Right Sidebar - Table of Contents -->
	<?php docsraptor_output_toc_sidebar(); ?>
</div>

<?php docsraptor_output_search_modal(); ?>
