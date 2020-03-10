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

	/**
	 * Data provider for test_get_original_url_with_size.
	 *
	 * @return array
	 */
	public function get_url_with_size_data() {
		return [
			[ 'http://www.example.com/test.jpg', 222, 444, [], 'http://www.example.com/test.jpg?w=222&h=444' ],
			[ 'http://www.example.com/test.jpg', 100, 100, [], 'http://www.example.com/test.jpg?w=100&h=100' ],
			[ 'http://www.example.com/test.jpg', -1, -1, [], 'http://www.example.com/test.jpg?w=1&h=1' ],
			[ 'http://www.example.com/test.jpg', 'invalid', 'invalid', [], 'http://www.example.com/test.jpg?w=0&h=0' ],
			[ 'http://www.example.com/test.jpg', 100, 100, [ 'crop' => true ], 'http://www.example.com/test.jpg?w=100&h=100&crop=1' ],
			[ 'http://www.example.com/test.jpg?crop=1', 100, 100, [], 'http://www.example.com/test.jpg?crop=1&w=100&h=100' ],
		];
	}

	/**
	 * Test get_original_url_with_size.
	 *
	 * @covers ::get_original_url_with_size()
	 * @dataProvider get_url_with_size_data
	 *
	 * @param string $url Original URL of unsplash asset.
	 * @param int    $width Width of image.
	 * @param int    $height Height of image.
	 * @param array  $attr Other attributes to be passed to the URL.
	 * @param string $expected Expected value.
	 */
	public function test_get_original_url_with_size( $url, $width, $height, $attr, $expected ) {
		$plugin = get_plugin_instance();
		$this->assertSame( $plugin->get_original_url_with_size( $url, $width, $height, $attr ), $expected );
	}
}
