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

if ( ! defined( 'UNSPLASH_APP_ID' ) ) {
	define( 'UNSPLASH_APP_ID', getenv( 'UNSPLASH_APP_ID' ) );
}
if ( ! defined( 'UNSPLASH_APP_SECRET' ) ) {
	define( 'UNSPLASH_APP_SECRET', getenv( 'UNSPLASH_APP_SECRET' ) );
}

$router = new Router( new Plugin( __FILE__ ) );

add_action( 'plugins_loaded', [ $router, 'init' ] );
