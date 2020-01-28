<?php
/**
 * Bootstrap PHPUnit related dependencies.
 *
 * @package Unsplash
 */

global $_plugin_files;
$_plugin_files = array();

// Tests directory.
$_tests_dir = '/var/www/html/wp-content/plugins/unsplash-wp/vendor/xwp/wordpress-tests/phpunit';

if ( ! file_exists( $_tests_dir . '/includes/' ) ) {
	trigger_error( 'Unable to locate wordpress-tests', E_USER_ERROR ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
}
require_once $_tests_dir . '/includes/functions.php';

// Build the plugins directory search array.
$_plugins_array = glob( '/var/www/html/wp-content/plugins/*' );

// Build the plugin files array.
foreach ( $_plugins_array as $_plugin_candidate ) {
	if ( is_dir( $_plugin_candidate ) && 'akismet' !== basename( $_plugin_candidate ) ) {
		foreach ( glob( $_plugin_candidate . '/*.php' ) as $_plugin_file_candidate ) {
			if ( basename( $_plugin_file_candidate ) !== 'unsplash.php' && basename( $_plugin_candidate ) !== basename( $_plugin_file_candidate, '.php' ) ) {
				continue;
			}
			// @codingStandardsIgnoreStart
			$_plugin_file_src = file_get_contents( $_plugin_file_candidate );
			// @codingStandardsIgnoreEnd
			if ( preg_match( '/Plugin\s*Name\s*:/', $_plugin_file_src ) ) {
				$_plugin_files[] = $_plugin_file_candidate;
				break;
			}
		}
	}
}

if ( empty( $_plugin_files ) ) {
	trigger_error( 'Unable to locate any files containing a plugin metadata block.', E_USER_ERROR ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
}

unset( $_plugins, $_plugin_candidate, $_plugin_file_candidate, $_plugin_file_src );

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
