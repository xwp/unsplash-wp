<?php
/**
 * Bootstrap PHPUnit related dependencies.
 *
 * @package Unsplash
 */

global $_plugin_files;
$_plugin_files = array();

$_plugin_root = realpath( __DIR__ . '/..' );

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

// Build the plugins directory search array.
$_plugins_array = glob( realpath( __DIR__ . '/../..' ) . '/*' );

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

unset( $_plugin_candidate, $_plugin_file_candidate, $_plugin_file_src );

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
