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
		$plugin = get_plugin_instance();
		$this->assertEquals( 10, has_action( 'plugins_loaded', [ $plugin, 'init' ] ) );
		$this->assertEquals( 10, has_action( 'wp_enqueue_media', [ $plugin, 'enqueue_media_scripts' ] ) );
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
	 * Test for enqueue_media_scripts() method.
	 *
	 * @see Plugin::enqueue_media_scripts()
	 */
	public function test_enqueue_media_scripts() {
		$plugin = get_plugin_instance();
		$plugin->enqueue_media_scripts();
		$this->assertTrue( wp_script_is( 'unsplash-media-selector', 'enqueued' ) );
	}

	/**
	 * Test for wp_prepare_attachment_for_js() method.
	 *
	 * @see Plugin::wp_prepare_attachment_for_js()
	 */
	public function test_wp_prepare_attachment_for_js() {
		$plugin = get_plugin_instance();
		$image  = [
			'id'              => 'eOvv4N6yNmk',
			'tags'            => [],
			'height'          => 123,
			'width'           => 456,
			'description'     => 'test description',
			'alt_description' => 'test alt description',
			'urls'            => [
				'raw'   => 'http://www.example.com/test.jpg',
				'thumb' => 'http://www.example.com/thumb.jpg',
			],
		];
		$output = $plugin->wp_prepare_attachment_for_js( $image );
		$this->assertEquals( $output['id'], $image['id'] );
		$this->assertEquals( $output['alt'], $image['alt_description'] );
		$this->assertEquals( $output['description'], $image['description'] );
		$this->assertEquals( $output['icon'], 'http://www.example.com/thumb.jpg?w=150&h=150&q=85&fm=jpg' );
		$this->assertEquals( $output['sizes']['full']['height'], $image['height'] );
		$this->assertEquals( $output['sizes']['full']['width'], $image['width'] );
		$this->assertEquals( $output['sizes']['full']['url'], $image['urls']['raw'] );
		$this->assertEquals( $output['sizes']['thumbnail']['url'], 'http://www.example.com/test.jpg?w=150&h=150&q=85&fm=jpg' );
		$this->assertEquals( $output['sizes']['medium_large']['url'], 'http://www.example.com/test.jpg?w=768&h=208&q=85&fm=jpg' );
	}
}
