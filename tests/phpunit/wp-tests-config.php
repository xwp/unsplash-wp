<?php
/**
 * WordPress Test Config.
 *
 * @package Unsplash
 */

define( 'ABSPATH', __DIR__ . '/../../data/wordpress/html/' );
define( 'DIR_TESTDATA', __DIR__ . '/../../data/' );

// Test with WordPress debug mode (default).
define( 'WP_DEBUG', true );

// ** MySQL settings ** //

// This configuration file will be used by the copy of WordPress being tested.
// wordpress/wp-config.php will be ignored.

// WARNING WARNING WARNING!
// These tests will DROP ALL TABLES in the database with the prefix named below.
// DO NOT use a production database or one that is shared with something else.

define( 'DB_HOST', 'mysql' );
define( 'DB_NAME', 'wptests' );
define( 'DB_USER', 'wptests' );
define( 'DB_PASSWORD', 'wptests' );

define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_';  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

define( 'WP_PHP_BINARY', 'php' );

define( 'WPLANG', '' );
