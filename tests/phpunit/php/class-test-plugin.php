<?php
/**
 * Tests for Plugin class.
 *
 * @package Unsplash
 */

namespace Unsplash;

/**
 * Tests for Plugin class.
 */
class Test_Plugin extends \WP_UnitTestCase {

	/**
	 * Test constructor.
	 *
	 * @see Plugin::__construct()
	 */
	public function test_construct() {
		$plugin = new Plugin();
		$this->assertEquals( 10, has_action( 'plugins_loaded', [ $plugin, 'init' ] ) );
		$this->assertEquals( 10, has_action( 'admin_enqueue_scripts', [ $plugin, 'admin_enqueue_scripts' ] ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_query-unsplash', [ $plugin, 'wp_ajax_query_unsplash' ] ) );
		$this->assertEquals( false, has_action( 'wp_ajax_send-attachment-to-editor', 'wp_ajax_send_attachment_to_editor' ) );
		$this->assertEquals( 0, has_action( 'wp_ajax_send-attachment-to-editor', [ $plugin, 'wp_ajax_send_attachment_to_editor' ] ) );
		$this->assertEquals( 10, has_action( 'init', [ $plugin, 'register_taxonomy' ] ) );
		$this->assertEquals( 10, has_action( 'init', [ $plugin, 'register_meta' ] ) );
	}

	/**
	 * Test for init() method.
	 *
	 * @see Plugin::init()
	 */
	public function test_init() {
		$plugin = get_plugin_instance();
		$plugin->init();

		$this->assertInstanceOf( Hotlink::class, $plugin->hotlink );
		$this->assertInstanceOf( Settings::class, $plugin->settings );
		$this->assertInstanceOf( REST_Controller::class, $plugin->rest_controller );
	}

	/**
	 * Test for admin_enqueue_scripts() method.
	 *
	 * @see Plugin::admin_enqueue_scripts()
	 */
	public function test_admin_enqueue_scripts() {
		$plugin = get_plugin_instance();
		$plugin->admin_enqueue_scripts();
		$this->assertTrue( wp_script_is( 'unsplash-media-views', 'enqueued' ) );
	}
}
