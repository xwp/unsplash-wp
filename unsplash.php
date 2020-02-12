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
$unsplash['router']          = new Router( $unsplash['plugin'] );
$unsplash['hotlink']         = new Hotlink( $unsplash['router'] );
$unsplash['settings']        = new Settings( $unsplash['plugin'] );
$unsplash['rest_controller'] = new RestController( $unsplash['settings'], $unsplash['plugin'] );

// Initialize Router.
add_action( 'plugins_loaded', [ $unsplash['router'], 'init' ] );

// Initialize Hotlink.
add_action( 'template_redirect', [ $unsplash['hotlink'], 'init' ] );

// Initialize Settings.
add_action( 'plugins_loaded', [ $unsplash['settings'], 'init' ] );

// Initialize REST Controller.
add_action( 'rest_api_init', [ $unsplash['rest_controller'], 'register_routes' ] );
