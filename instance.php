<?php
/**
 * Instantiates the Unsplash plugin
 *
 * @package Unsplash
 */

namespace Unsplash;

// Support for site-level autoloading.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

global $unsplash_plugin;

require_once __DIR__ . '/php/class-plugin-base.php';
require_once __DIR__ . '/php/class-plugin.php';

$unsplash_plugin = new Plugin();

/**
 * Unsplash Plugin Instance
 *
 * @return Plugin
 */
function get_plugin_instance() {
	global $unsplash_plugin;
	return $unsplash_plugin;
}
