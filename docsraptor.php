<?php
/**
 * Docs Raptor
 *
 * @package docsraptor
 * @author Jon Schroeder
 *
 * @wordpress-plugin
 * Plugin Name:    Docs Raptor
 * Plugin URI:     https://elod.in
 * Description:    Create documentation and knowledge bases for anything.
 * Version:        0.3.4
 * Author:         Jon Schroeder
 * Author URI:     https://elod.in
 * Text Domain:    docsraptor
 * License:        GPLv2 or later
 * License URI:    http://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access to the plugin.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Sorry, you are not allowed to access this page directly.' );
}

// Define the version of the plugin.
define( 'DOCSRAPTOR_VERSION', '0.3.4' );

// Set up plugin directories.
define( 'DOCSRAPTOR_DIR', plugin_dir_path( __FILE__ ) );
define( 'DOCSRAPTOR_PATH', plugin_dir_url( __FILE__ ) );
define( 'DOCSRAPTOR_BASENAME', plugin_basename( __FILE__ ) );
define( 'DOCSRAPTOR_FILE', __FILE__ );

// Plugin directory
define( 'DOCSRAPTOR', dirname( __FILE__ ) );

/**
 * Load the files
 *
 * @param   string $directory  the path to the directory to load.
 * @return  void
 */
function docsraptor_require_files_recursive( $directory ) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $directory, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::LEAVES_ONLY
	);

	foreach ( $iterator as $file ) {
		if ( $file->isFile() && $file->getExtension() === 'php' ) {
			require_once $file->getPathname();
		}
	}
}

// Require_once all files in /lib and its subdirectories.
docsraptor_require_files_recursive( DOCSRAPTOR_DIR . 'lib' );

// Register the block template used by block themes for single docs.
add_action( 'init', 'docsraptor_register_block_templates' );
function docsraptor_register_block_templates() {
	if ( ! function_exists( 'register_block_template' ) ) {
		return;
	}

	register_block_template(
		'docsraptor//single-docs',
		array(
			'title'       => __( 'Single Doc', 'docsraptor' ),
			'description' => __( 'Displays a single Docs Raptor document.', 'docsraptor' ),
			'content'     => '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->' . "\n\n" .
				'<!-- wp:docsraptor/single-doc-content -->' . "\n" .
				'<!-- wp:post-title {"level":1} /-->' . "\n" .
				'<!-- wp:post-date {"displayType":"modified","className":"docs-meta","format":"F j, Y"} /-->' . "\n" .
				'<!-- wp:post-content {"className":"docs-content"} /-->' . "\n" .
				'<!-- /wp:docsraptor/single-doc-content -->' . "\n\n" .
				'<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->',
			'post_types'  => array( 'docs' ),
		)
	);

	foreach ( array( 'docs-categories', 'docs-collections' ) as $taxonomy ) {
		register_block_template(
			'docsraptor//taxonomy-' . $taxonomy,
			array(
				'title'       => __( 'Docs Taxonomy', 'docsraptor' ),
				'description' => __( 'Displays a Docs Raptor taxonomy archive.', 'docsraptor' ),
				'content'     => '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->' . "\n\n" .
					'<!-- wp:docsraptor/taxonomy-docs-content -->' . "\n" .
					'<!-- wp:query-title {"type":"archive","showPrefix":false} /-->' . "\n" .
					'<!-- wp:term-description {"className":"docs-description"} /-->' . "\n" .
					'<!-- /wp:docsraptor/taxonomy-docs-content -->' . "\n\n" .
					'<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->',
			)
		);
	}
}

// Enqueue styles and scripts.
add_action( 'wp_enqueue_scripts', 'docsraptor_enqueue_assets' );
function docsraptor_enqueue_assets() {
	if ( is_singular( 'docs' ) || is_tax( array( 'docs-categories', 'docs-collections' ) ) || docsraptor_current_query_has_search_block() ) {
		docsraptor_enqueue_frontend_assets();
	}
}

/**
 * Enqueue frontend assets used by docs layouts and search.
 */
function docsraptor_enqueue_frontend_assets() {
	static $has_enqueued = false;

	if ( $has_enqueued ) {
		return;
	}

	$has_enqueued = true;

	if ( is_singular( 'docs' ) || is_tax( array( 'docs-categories', 'docs-collections' ) ) ) {
		$current_doc_id = is_singular( 'docs' ) ? get_queried_object_id() : null;
	} else {
		$current_doc_id = null;
	}

	// Styles
	wp_enqueue_style( 'docsraptor-main', DOCSRAPTOR_PATH . 'assets/css/main.css', array(), DOCSRAPTOR_VERSION );

	// Scripts
	wp_enqueue_script( 'docsraptor-search', DOCSRAPTOR_PATH . 'assets/js/search.js', array(), DOCSRAPTOR_VERSION, true );
	wp_localize_script(
		'docsraptor-search',
		'docsraptorSearch',
		array(
			'collectionId' => docsraptor_get_current_collection_id( $current_doc_id ),
		)
	);

	if ( is_singular( 'docs' ) || is_tax( array( 'docs-categories', 'docs-collections' ) ) ) {
		wp_enqueue_script( 'docsraptor-sidebar', DOCSRAPTOR_PATH . 'assets/js/sidebar.js', array(), DOCSRAPTOR_VERSION, true );
		wp_localize_script(
			'docsraptor-sidebar',
			'docsraptorSidebar',
			array(
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'canReorder' => current_user_can( 'manage_options' ),
				'nonce'      => wp_create_nonce( 'docsraptor_reorder_docs' ),
			)
		);
		wp_enqueue_script( 'docsraptor-toc', DOCSRAPTOR_PATH . 'assets/js/toc.js', array(), DOCSRAPTOR_VERSION, true );
	}
}

/**
 * Check whether the current queried post contains the docs search block.
 *
 * @return bool
 */
function docsraptor_current_query_has_search_block() {
	if ( ! is_singular() ) {
		return false;
	}

	$post = get_post();

	return $post && has_block( 'docsraptor/docs-search', $post );
}

// Include custom template for docs CPT and taxonomy if theme doesn't have one.
add_filter( 'template_include', 'docsraptor_template_include' );
function docsraptor_template_include( $template ) {
	$is_block_theme = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();

	if ( is_singular( 'docs' ) && ! $is_block_theme && ! locate_template( 'single-docs.php' ) ) {
		$plugin_template = DOCSRAPTOR_DIR . 'templates/single-docs.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
	}

	if ( is_tax( 'docs-categories' ) && ! $is_block_theme && ! locate_template( 'taxonomy-docs-categories.php' ) ) {
		$plugin_template = DOCSRAPTOR_DIR . 'templates/taxonomy-docs-categories.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
	}

	if ( is_tax( 'docs-collections' ) && ! $is_block_theme && ! locate_template( 'taxonomy-docs-collections.php' ) ) {
		$plugin_template = DOCSRAPTOR_DIR . 'templates/taxonomy-docs-categories.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
	}

	return $template;
}
