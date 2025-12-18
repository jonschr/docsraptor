<?php
/**
 * Template for displaying docs category archives.
 *
 * @package docsraptor
 */

get_header();

$term = get_queried_object();

// Safety check - ensure we have a valid term object.
if ( ! $term || ! isset( $term->term_id ) ) {
	echo '<p>' . esc_html__( 'Category not found.', 'docsraptor' ) . '</p>';
	get_footer();
	return;
}
?>

<div class="docs-container">
	<!-- Left Sidebar -->
	<div class="docs-sidebar">
		<div class="docs-sidebar-content">
			<?php docsraptor_output_sidebar( null, $term->term_id ); ?>
		</div>
	</div>

	<!-- Mobile/Tablet Search (hidden on desktop) -->
	<?php docsraptor_output_mobile_search(); ?>

	<!-- Main Content -->
	<div class="docs-main">
		<?php docsraptor_output_term_breadcrumbs( $term ); ?>

		<h1><?php echo esc_html( $term->name ); ?></h1>
		
		<?php if ( ! empty( $term->description ) ) : ?>
			<div class="docs-description">
				<?php echo wpautop( esc_html( $term->description ) ); ?>
			</div>
		<?php endif; ?>

		<div class="docs-content">
			<?php
			// Get child categories.
			$child_terms = get_terms( array(
				'taxonomy'   => 'docs-categories',
				'parent'     => $term->term_id,
				'hide_empty' => false,
				'orderby'    => 'term_order',
				'order'      => 'ASC',
			) );

			// Filter out empty child terms.
			$child_terms = array_filter( $child_terms, function( $child ) {
				$posts = get_posts( array(
					'post_type'      => 'docs',
					'tax_query'      => array(
						array(
							'taxonomy'         => 'docs-categories',
							'field'            => 'term_id',
							'terms'            => $child->term_id,
							'include_children' => true,
						),
					),
					'posts_per_page' => 1,
					'fields'         => 'ids',
				) );
				return ! empty( $posts );
			} );

			// Get posts directly in this category (not in child categories).
			$direct_posts = get_posts( array(
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
			) );

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
									<a href="<?php echo esc_url( get_term_link( $child_term ) ); ?>">
										<?php echo esc_html( $child_term->name ); ?>
									</a>
								</h2>
								<?php if ( ! empty( $child_term->description ) ) : ?>
									<p class="docs-subcategory-description"><?php echo esc_html( $child_term->description ); ?></p>
								<?php endif; ?>
								<?php
								// Get posts in this child category.
								$child_posts = get_posts( array(
									'post_type'      => 'docs',
									'tax_query'      => array(
										array(
											'taxonomy'         => 'docs-categories',
											'field'            => 'term_id',
											'terms'            => $child_term->term_id,
											'include_children' => false,
										),
									),
									'posts_per_page' => 5,
									'orderby'        => 'menu_order title',
									'order'          => 'ASC',
								) );

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

<?php
get_footer();
