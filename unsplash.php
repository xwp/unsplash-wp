<?php
/**
 * Plugin Name: Unsplash
 * Description: Unsplash for WordPress.
 * Version: 1.0.0
 * Author: XWP
 * Author URI: https://github.com/xwp/unsplash-wp
 * Text Domain: unsplash
 * Requires at least: 4.9
 * Requires PHP: 5.6
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

// Support for site-level autoloading.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

global $unsplash_plugin, $unsplash_router;

$unsplash_plugin = new Plugin( __FILE__ );
$unsplash_router = new Router( $unsplash_plugin );

/**
 * Router Instance.
 *
 * @return Router
 */
function get_router_instance() {
	global $unsplash_router;
	return $unsplash_router;
}

add_action( 'plugins_loaded', [ $unsplash_router, 'init' ] );
