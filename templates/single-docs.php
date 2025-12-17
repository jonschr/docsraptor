<?php
/**
 * Template for displaying single docs.
 *
 * @package docsraptor
 */

get_header();

if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		?>

		<div class="docs-container">
			<!-- Left Sidebar -->
			<div class="docs-sidebar">
				<div class="docs-sidebar-content">
					<?php
						// Get all resource types hierarchically
						function get_terms_hierarchical($taxonomy) {
							$terms = get_terms(array(
								'taxonomy' => $taxonomy,
								'hide_empty' => false,
								'orderby' => 'term_order',
								'order' => 'ASC',
							));
							
							$organized_terms = array();
							$terms_by_id = array();
							
							foreach ($terms as $term) {
								$terms_by_id[$term->term_id] = $term;
								if ($term->parent == 0) {
									$organized_terms[] = $term;
								}
							}
							
							foreach ($terms as $term) {
								if ($term->parent != 0 && isset($terms_by_id[$term->parent])) {
									if (!isset($terms_by_id[$term->parent]->children)) {
										$terms_by_id[$term->parent]->children = array();
									}
									$terms_by_id[$term->parent]->children[] = $term;
								}
							}
							
							// Sort organized_terms and children by term_order
							usort($organized_terms, function($a, $b) {
								return $a->term_order - $b->term_order;
							});
							
							foreach ($organized_terms as $term) {
								if (isset($term->children)) {
									usort($term->children, function($a, $b) {
										return $a->term_order - $b->term_order;
									});
								}
							}
							
							// Filter out empty terms (no posts and no children with posts)
							$organized_terms = array_filter($organized_terms, 'term_has_content');
							
							return $organized_terms;
						}
						
						// Check if a term has posts or children with posts
						function term_has_content($term) {
							// Check if term has posts directly
							$args = array(
								'post_type' => 'docs',
								'tax_query' => array(
									array(
										'taxonomy' => 'docs-categories',
										'field' => 'term_id',
										'terms' => $term->term_id,
										'include_children' => false,
									),
								),
								'posts_per_page' => 1,
								'fields' => 'ids',
							);
							$posts = get_posts($args);
							if (!empty($posts)) {
								return true;
							}
							
							// Check if any children have content
							if (isset($term->children) && !empty($term->children)) {
								// Filter children recursively
								$term->children = array_filter($term->children, 'term_has_content');
								if (!empty($term->children)) {
									return true;
								}
							}
							
							return false;
						}
						
						function display_terms_hierarchy($terms, $current_post_id, $deepest_term_id, $level = 0) {
							if ($level === 0) {
								echo '<ul class="docs-list open">';
							}
							foreach ($terms as $term) {
								$post_terms = wp_get_post_terms($current_post_id, 'docs-categories', array('fields' => 'ids'));
								$all_current_terms = $post_terms;
								foreach ($post_terms as $term_id) {
									$ancestors = get_ancestors($term_id, 'docs-categories');
									$all_current_terms = array_merge($all_current_terms, $ancestors);
								}
								$current_term = in_array($term->term_id, $all_current_terms);
								
								$has_children = isset($term->children) && !empty($term->children);
								
								// Check if term has posts
								$term_posts_args = array(
									'post_type' => 'docs',
									'tax_query' => array(
										array(
											'taxonomy' => 'docs-categories',
											'field' => 'term_id',
											'terms' => $term->term_id,
											'include_children' => false,
										),
									),
									'posts_per_page' => -1,
									'orderby' => 'menu_order title',
									'order' => 'ASC',
								);
								$term_posts = get_posts($term_posts_args);
								$has_content = !empty($term_posts) || $has_children;
								?>
								<li class="docs-category-item <?php echo $current_term ? 'current' : ''; ?>">
									<div class="docs-category <?php echo $level > 0 ? 'child' : 'parent'; ?> <?php echo $current_term ? 'current' : ''; ?> <?php echo $has_content ? 'has-children' : ''; ?>">
											<div class="docs-category-toggle <?php echo !$has_content ? 'no-toggle' : ''; ?>" role="button" tabindex="0" aria-expanded="<?php echo $current_term ? 'true' : 'false'; ?>">
												<a href="<?php echo esc_url(get_term_link($term)); ?>" class="docs-category-link"><?php echo esc_html($term->name); ?></a>
										</div>
										<?php
										if ($has_content) :
											?>
											<ul class="docs-list <?php echo $current_term ? 'open' : ''; ?>">
												<?php foreach ($term_posts as $post_item) : ?>
													<li class="docs-post <?php echo (get_the_ID() === $post_item->ID && $term->term_id === $deepest_term_id) ? 'current' : ''; ?>">
														<a href="<?php echo get_permalink($post_item->ID); ?>">
															<?php echo esc_html($post_item->post_title); ?>
														</a>
													</li>
												<?php endforeach; ?>
												<?php
												if ($has_children) {
													display_terms_hierarchy($term->children, $current_post_id, $deepest_term_id, $level + 1);
												}
												?>
											</ul>
										<?php endif; ?>
									</div>
								</li>
								<?php
							}
							if ($level === 0) {
								echo '</ul>';
							}
						}
						
						// Get uncategorized posts
						$uncategorized_args = array(
							'post_type' => 'docs',
							'tax_query' => array(
								array(
									'taxonomy' => 'docs-categories',
									'operator' => 'NOT EXISTS',
								),
							),
							'posts_per_page' => -1,
							'orderby' => 'menu_order title',
							'order' => 'ASC',
						);
						$uncategorized_posts = get_posts( $uncategorized_args );

						if ( ! empty( $uncategorized_posts ) ) :
							foreach ( $uncategorized_posts as $post_item ) :
								$current = ( get_the_ID() === $post_item->ID ) ? 'current' : '';
								?>
								<div class="docs-type uncategorized-post <?php echo $current; ?>">
									<a href="<?php echo get_permalink( $post_item->ID ); ?>">
										<?php echo esc_html( $post_item->post_title ); ?>
									</a>
								</div>
								<?php
							endforeach;
						endif;

						$terms = get_terms_hierarchical('docs-categories');
						
						// Find the deepest term for the current post to highlight only there
						$current_post_terms = wp_get_post_terms(get_the_ID(), 'docs-categories', array('fields' => 'ids'));
						$deepest_term_id = null;
						$max_depth = -1;
						foreach ($current_post_terms as $term_id) {
							$ancestors = get_ancestors($term_id, 'docs-categories');
							$depth = count($ancestors);
							if ($depth > $max_depth) {
								$max_depth = $depth;
								$deepest_term_id = $term_id;
							}
						}
						
						if (!empty($terms) && !is_wp_error($terms)) :
							display_terms_hierarchy($terms, get_the_ID(), $deepest_term_id);
						endif;

						?>
				</div>
			</div>

			<!-- Main Content -->
			<div class="docs-main">
				<?php
				// Breadcrumbs
				$post_terms = wp_get_post_terms( get_the_ID(), 'docs-categories' );
				if ( ! empty( $post_terms ) && ! is_wp_error( $post_terms ) ) :
					$term = $post_terms[0]; // Assuming single term
					?>
					<div class="docs-breadcrumbs">
						<span><?php echo esc_html( $term->name ); ?></span> &gt; <span><?php the_title(); ?></span>
					</div>
				<?php endif; ?>

				<h1><?php the_title(); ?></h1>
				<div class="docs-meta">
					Last updated: <?php echo get_the_modified_date(); ?>
				</div>
				<div class="docs-content">
					<?php the_content(); ?>
				</div>
			</div>

			<!-- Right Sidebar - Table of Contents -->
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
		</div>

		<!-- Search Modal -->
		<div id="docs-search-modal" class="docs-search-modal">
			<div class="docs-search-modal-content">
				<input type="search" id="docs-modal-search" placeholder="Search docs..." class="docs-search-modal-input" />
				<div class="docs-search-suggestions-modal"></div>
			</div>
		</div>

		<?php
	endwhile;
endif;

get_footer();
?>