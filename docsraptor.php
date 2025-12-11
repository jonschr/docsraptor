<?php
/**
 * Docs Raptor
 *
 * @package elodin-resources
 * @author Jon Schroeder
 *
 * @wordpress-plugin
 * Plugin Name:    Docs Raptor
 * Plugin URI:     https://elod.in
 * Description:    Create documentation and knowledge bases for anything.
 * Version:        0.1
 * Author:         Jon Schroeder
 * Author URI:     https://elod.in
 * Text Domain:    elodin-resources
 * License:        GPLv2 or later
 * License URI:    http://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access to the plugin.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Sorry, you are not allowed to access this page directly.' );
}

// Define the version of the plugin.
define( 'ELODIN_RESOURCES_VERSION', '0.1' );

// Set up plugin directories.
define( 'ELODIN_RESOURCES_DIR', plugin_dir_path( __FILE__ ) );
define( 'ELODIN_RESOURCES_PATH', plugin_dir_url( __FILE__ ) );
define( 'ELODIN_RESOURCES_BASENAME', plugin_basename( __FILE__ ) );
define( 'ELODIN_RESOURCES_FILE', __FILE__ );

// Plugin directory
define( 'ELODIN_RESOURCES', dirname( __FILE__ ) );

/**
 * Load the files
 *
 * @param   string $directory  the path to the directory to load.
 * @return  void
 */
function elodin_resources_require_files_recursive( $directory ) {
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
elodin_resources_require_files_recursive( ELODIN_RESOURCES_DIR . 'lib' );

// Enqueue styles and scripts.
add_action( 'wp_enqueue_scripts', 'elodin_resources_enqueue_assets' );
function elodin_resources_enqueue_assets() {
	if ( is_singular( 'resources' ) ) {
		wp_enqueue_style( 'elodin-resources-main', ELODIN_RESOURCES_PATH . 'assets/css/main.css', array(), ELODIN_RESOURCES_VERSION );
		wp_enqueue_script( 'elodin-resources-js', ELODIN_RESOURCES_PATH . 'assets/js/resources.js', array(), ELODIN_RESOURCES_VERSION, true );
	}
}

// Include custom template for resources CPT if theme doesn't have one.
add_filter( 'template_include', 'elodin_resources_template_include' );
function elodin_resources_template_include( $template ) {
	if ( is_singular( 'resources' ) && ! locate_template( 'single-resources.php' ) ) {
		$plugin_template = ELODIN_RESOURCES_DIR . 'templates/single-resources.php';
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
	}
	return $template;
}
