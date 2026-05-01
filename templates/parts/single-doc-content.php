<?php
/**
 * Shared content for displaying a single doc.
 *
 * @package docsraptor
 */

?>

<div class="docs-container">
	<!-- Left Sidebar (desktop) -->
	<div class="docs-sidebar docs-sidebar-desktop">
		<div class="docs-sidebar-content">
			<?php docsraptor_output_sidebar( get_the_ID() ); ?>
		</div>
	</div>

	<!-- Mobile Sidebar (collapsible) -->
	<details class="docs-sidebar-mobile">
		<summary>
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
			Menu
		</summary>
		<div class="docs-sidebar-content">
			<?php docsraptor_output_sidebar( get_the_ID() ); ?>
		</div>
	</details>

	<!-- Mobile Search -->
	<?php docsraptor_output_mobile_search(); ?>

	<!-- Main Content -->
	<div class="docs-main">
		<?php docsraptor_output_breadcrumbs( get_the_ID() ); ?>

		<?php if ( ! empty( $main_content ) ) : ?>
			<?php echo $main_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php else : ?>
			<h1><?php the_title(); ?></h1>
			<div class="docs-meta">
				Last updated: <?php echo get_the_modified_date(); ?>
			</div>
			<div class="docs-content">
				<?php the_content(); ?>
			</div>
		<?php endif; ?>
	</div>

	<!-- Right Sidebar - Table of Contents -->
	<?php docsraptor_output_toc_sidebar(); ?>
</div>

<?php docsraptor_output_search_modal(); ?>
