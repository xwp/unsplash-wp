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

global $unsplash;

$unsplash['plugin']          = new Plugin( __FILE__ );
$unsplash['rest_controller'] = new RestController();
$unsplash['router']          = new Router( $unsplash['plugin'] );
$unsplash['settings']        = new Settings( $unsplash['plugin'] );

// Initialize REST Controller.
add_action( 'rest_api_init', [ $unsplash['rest_controller'], 'register_routes' ] );

// Initialize Router.
add_action( 'plugins_loaded', [ $unsplash['router'], 'init' ] );

// Initialize Settings.
add_action( 'plugins_loaded', [ $unsplash['settings'], 'init' ] );
