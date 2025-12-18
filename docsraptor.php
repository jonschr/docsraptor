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
 * Version:        0.2.3
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
define( 'DOCSRAPTOR_VERSION', '0.2.3' );

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

// Enqueue styles and scripts.
add_action( 'wp_enqueue_scripts', 'docsraptor_enqueue_assets' );
function docsraptor_enqueue_assets() {
	if ( is_singular( 'docs' ) || is_tax( 'docs-categories' ) ) {
		wp_enqueue_style( 'docsraptor-main', DOCSRAPTOR_PATH . 'assets/css/main.css', array(), DOCSRAPTOR_VERSION );
		wp_enqueue_script( 'docsraptor-js', DOCSRAPTOR_PATH . 'assets/js/docs.js', array(), DOCSRAPTOR_VERSION, true );
	}
}

// Include custom template for docs CPT and taxonomy if theme doesn't have one.
add_filter( 'template_include', 'docsraptor_template_include' );
function docsraptor_template_include( $template ) {
	if ( is_singular( 'docs' ) && ! locate_template( 'single-docs.php' ) ) {
		$plugin_template = DOCSRAPTOR_DIR . 'templates/single-docs.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
	}

	if ( is_tax( 'docs-categories' ) && ! locate_template( 'taxonomy-docs-categories.php' ) ) {
		$plugin_template = DOCSRAPTOR_DIR . 'templates/taxonomy-docs-categories.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
	}

	return $template;
}
