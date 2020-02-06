<?php
/**
 * Bootstrap PHPUnit related dependencies.
 *
 * @package Unsplash
 */

global $_plugin_files;

$_plugin_root = realpath( __DIR__ . '/../..' );

$_plugin_files = [
	"${_plugin_root}/unsplash.php",
];

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = "${_plugin_root}/vendor/xwp/wordpress-tests/phpunit";
	define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );
}

define( 'DIR_TESTDATA', dirname( __FILE__ ) . '/../../data' );

if ( ! file_exists( $_tests_dir . '/includes/' ) ) {
	trigger_error( 'Unable to locate wordpress-tests', E_USER_ERROR ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
}
require_once $_tests_dir . '/includes/functions.php';

/**
 * Loads the plugins for testing.
 */
function unit_test_load_plugin_file() {
	global $_plugin_files;

	// Load the plugins.
	foreach ( $_plugin_files as $file ) {
		require_once "$file";
	}
	unset( $_plugin_files );
}
tests_add_filter( 'muplugins_loaded', 'unit_test_load_plugin_file' );

// Run Integration Tests.
require_once $_tests_dir . '/includes/bootstrap.php';
