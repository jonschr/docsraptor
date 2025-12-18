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
					<?php docsraptor_output_sidebar( get_the_ID() ); ?>
				</div>
			</div>

			<!-- Mobile/Tablet Search (hidden on desktop) -->
			<?php docsraptor_output_mobile_search(); ?>

			<!-- Main Content -->
			<div class="docs-main">
				<?php docsraptor_output_breadcrumbs( get_the_ID() ); ?>

				<h1><?php the_title(); ?></h1>
				<div class="docs-meta">
					Last updated: <?php echo get_the_modified_date(); ?>
				</div>
				<div class="docs-content">
					<?php the_content(); ?>
				</div>
			</div>

			<!-- Right Sidebar - Table of Contents -->
			<?php docsraptor_output_toc_sidebar(); ?>
		</div>

		<?php docsraptor_output_search_modal(); ?>

		<?php
	endwhile;
endif;

get_footer();