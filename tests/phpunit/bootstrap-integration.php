<?php
/**
 * Bootstrap PHPUnit related dependencies.
 *
 * @package Unsplash
 */

global $_plugin_files;

$_plugin_root = __DIR__ . '/../..';

$_plugin_files = [
	"${_plugin_root}/unsplash.php",
];

// Tests directory.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = "${_plugin_root}/vendor/xwp/wordpress-tests/phpunit";
}

if ( ! file_exists( $_tests_dir . '/includes/' ) ) {
	trigger_error( "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?", E_USER_ERROR ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error, WordPress.Security.EscapeOutput.OutputNotEscaped
}

// Give access to tests_add_filter() function.
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

if ( ! defined( 'WP_TESTS_CONFIG_FILE_PATH' ) && file_exists( '/.dockerenv' ) ) {
	// Load a customized WP tests config for the Docker based developer environment.
	define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );
}

// Run Integration Tests.
require_once $_tests_dir . '/includes/bootstrap.php';
