<?php
/**
 * Tests for Plugin class.
 *
 * @package Unsplash
 */

namespace Unsplash;

/**
 * Tests for Plugin class.
 *
 * @coversDefaultClass \Unsplash\Plugin
 */
class Test_Plugin extends \WP_UnitTestCase {

	/**
	 * Admin user for test.
	 *
	 * @var int
	 */
	protected static $admin_id;

	/**
	 * Subscriber user for test.
	 *
	 * @var int
	 */
	protected static $subscriber_id;


	/**
	 * Create fake data before our tests run.
	 *
	 * @param WP_UnitTest_Factory $factory Helper that lets us create fake data.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_id      = $factory->user->create(
			[ 'role' => 'administrator' ]
		);
		self::$subscriber_id = $factory->user->create(
			[ 'role' => 'subscriber' ]
		);
	}

	/**
	 * Test constructor.
	 *
	 * @see Plugin::__construct()
	 */
	public function test_construct() {
		$plugin = new Plugin();
		$this->assertEquals( 10, has_action( 'plugins_loaded', [ $plugin, 'init' ] ) );
		$this->assertEquals( 10, has_action( 'wp_default_scripts', [ $plugin, 'register_default_scripts' ] ) );
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
	 * Test for register_meta() method.
	 *
	 * @see Plugin::register_meta()
	 */
	public function test_register_meta() {
		$plugin = get_plugin_instance();
		$plugin->register_meta();
		$keys = get_registered_meta_keys( 'post', 'attachment' );

		$this->assertArrayHasKey( 'original_id', $keys );
		$this->assertArrayHasKey( 'original_url', $keys );
		$this->assertArrayHasKey( 'color', $keys );
		$this->assertArrayHasKey( 'unsplash_location', $keys );
		$this->assertArrayHasKey( 'unsplash_sponsor', $keys );
		$this->assertArrayHasKey( 'unsplash_exif', $keys );
	}

	/**
	 * Test for register_taxonomy() method.
	 *
	 * @see Plugin::register_taxonomy()
	 */
	public function test_register_taxonomy() {
		$plugin = get_plugin_instance();
		$plugin->register_taxonomy();

		$this->assertTrue( taxonomy_exists( 'media_tag' ) );
		$this->assertTrue( taxonomy_exists( 'media_source' ) );
		$this->assertTrue( taxonomy_exists( 'unsplash_user' ) );
	}

	/**
	 * Test for enqueue_media_scripts() method.
	 *
	 * @see Plugin::enqueue_media_scripts()
	 */
	public function test_enqueue_media_scripts() {
		wp_set_current_user( self::$admin_id );
		set_current_screen( 'post.php' );
		$plugin = get_plugin_instance();
		$plugin->enqueue_media_scripts();
		$this->assertTrue( wp_script_is( 'unsplash-media-selector', 'enqueued' ) );

		$featured_image_script_loads = version_compare( '5.0', get_bloginfo( 'version' ), '<=' );
		$this->assertEquals( $featured_image_script_loads, wp_script_is( 'unsplash-featured-image-selector', 'enqueued' ) );
	}

	/**
	 * Test for register_default_scripts() method.
	 *
	 * @see Plugin::register_default_scripts()
	 */
	public function test_register_default_scripts() {
		$wp_scripts = new \WP_Scripts();

		$plugin           = get_plugin_instance();
		$plugin_asset_url = $plugin->asset_url();

		$result = $plugin->register_default_scripts( $wp_scripts );

		if ( version_compare( '5.0', get_bloginfo( 'version' ), '<=' ) ) {
			$this->assertFalse( $result );
			return;
		}

		$expected_handles = [
			'wp-i18n',
			'wp-polyfill',
			'wp-url',
			'lodash',
		];

		foreach ( $expected_handles as $expected_handle ) {
			$this->assertContains( $expected_handle, array_keys( $wp_scripts->registered ) );
			$this->assertContains( $plugin_asset_url, $wp_scripts->registered[ $expected_handle ]->src );
		}
	}

	/**
	 * Test for enqueue_media_scripts() method doeesn't load on widget
	 *
	 * @see Plugin::enqueue_media_scripts()
	 */
	public function test_no_enqueue_media_scripts() {
		wp_set_current_user( self::$admin_id );
		set_current_screen( 'widget.php' );
		$plugin = get_plugin_instance();
		$this->assertFalse( $plugin->enqueue_media_scripts() );
	}

	/**
	 * Test for enqueue_media_scripts() method doeesn't load on widget
	 *
	 * @see Plugin::enqueue_media_scripts()
	 */
	public function test_not_logged_enqueue_media_scripts() {
		wp_set_current_user( self::$subscriber_id );
		set_current_screen( 'post.php' );
		$plugin = get_plugin_instance();
		$this->assertFalse( $plugin->enqueue_media_scripts() );
	}


	/**
	 * Test for enqueue_media_scripts() method doeesn't load on random screen
	 *
	 * @see Plugin::enqueue_media_scripts()
	 */
	public function test_no_random_enqueue_media_scripts() {
		wp_set_current_user( self::$admin_id );
		set_current_screen( 'unsplash.php' );
		$plugin = get_plugin_instance();
		$this->assertFalse( $plugin->enqueue_media_scripts() );
	}

	/**
	 * Test for image_sizes()
	 *
	 * @see Plugin::image_sizes()
	 */
	public function test_no_image_sizes() {
		$plugin = get_plugin_instance();
		add_filter( 'intermediate_image_sizes', '__return_empty_array' );
		$this->assertEqualSets( $plugin->image_sizes(), [] );
		remove_filter( 'intermediate_image_sizes', '__return_empty_array' );
	}

	/**
	 * Test for image_sizes()
	 *
	 * @see Plugin::image_sizes()
	 */
	public function test_image_sizes() {
		$plugin   = get_plugin_instance();
		$expected = [ 'large', 'medium', 'medium_large', 'thumbnail' ];

		if ( version_compare( '5.2', get_bloginfo( 'version' ), '<' ) ) {
			$expected[] = '1536x1536';
			$expected[] = '2048x2048';
		}

		$this->assertEqualSets( array_keys( $plugin->image_sizes() ), $expected );
	}

	/**
	 * Test for wp_prepare_attachment_for_js() method.
	 *
	 * @see Plugin::wp_prepare_attachment_for_js()
	 * @covers ::wp_prepare_attachment_for_js
	 * @covers ::add_image_sizes
	 * @covers ::get_image_height
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
		$this->assertEquals( $output['icon'], 'http://www.example.com/thumb.jpg?w=150&h=150&fm=jpg&q=85&fit=crop' );
		$this->assertEquals( $output['sizes']['full']['height'], $image['height'] );
		$this->assertEquals( $output['sizes']['full']['width'], $image['width'] );
		$this->assertEquals( $output['sizes']['full']['url'], $image['urls']['raw'] );
		$this->assertEquals( $output['sizes']['thumbnail']['url'], 'http://www.example.com/test.jpg?w=150&h=40&fm=jpg&q=85&fit=crop' );
		$this->assertEquals( $output['sizes']['medium_large']['url'], 'http://www.example.com/test.jpg?w=768&h=207&fm=jpg&q=85&fit=crop' );
	}

	/**
	 * Data provider for test_get_original_url_with_size.
	 *
	 * @return array
	 */
	public function get_url_with_size_data() {
		return [
			[ 'http://www.example.com/test.jpg', 222, 444, [], 'http://www.example.com/test.jpg?fm=jpg&q=85&fit=crop&w=222&h=444' ],
			[ 'http://www.example.com/test.jpg', 100, 100, [], 'http://www.example.com/test.jpg?fm=jpg&q=85&fit=crop&w=100&h=100' ],
			[ 'http://www.example.com/test.jpg', -1, -1, [], 'http://www.example.com/test.jpg?fm=jpg&q=85&fit=crop&w=1&h=1' ],
			[ 'http://www.example.com/test.jpg', 'invalid', 'invalid', [], 'http://www.example.com/test.jpg?fm=jpg&q=85&fit=crop&w=0&h=0' ],
			[ 'http://www.example.com/test.jpg', 100, 100, [ 'crop' => true ], 'http://www.example.com/test.jpg?fm=jpg&q=85&fit=crop&crop=1&w=100&h=100' ],
			[ 'http://www.example.com/test.jpg', 100, 100, [ 'crop' => false ], 'http://www.example.com/test.jpg?fm=jpg&q=85&fit=crop&crop=0&w=100&h=100' ],
			[ 'http://www.example.com/test.jpg?crop=1', 100, 100, [], 'http://www.example.com/test.jpg?crop=1&fm=jpg&q=85&fit=crop&w=100&h=100' ],
		];
	}

	/**
	 * Test get_original_url_with_size.
	 *
	 * @see Plugin::get_original_url_with_size()
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

	/**
	 * Test for admin_notice()
	 *
	 * @see Plugin::admin_notice()
	 */
	public function test_admin_notice() {
		add_filter( 'unsplash_api_credentials', [ $this, 'disable_unsplash_api_credentials' ] );
		wp_set_current_user( self::$admin_id );
		set_current_screen( 'post.php' );
		$plugin = get_plugin_instance();
		ob_start();
		$notice = $plugin->admin_notice();
		$output = ob_get_clean();
		$this->assertTrue( $notice );
		$this->assertContains( 'To complete set up of the Unsplash plugin you’ll need to add the API key/secret.', $output );
		remove_filter( 'unsplash_api_credentials', [ $this, 'disable_unsplash_api_credentials' ] );
	}

	/**
	 * Test for admin_notice()
	 *
	 * @see Plugin::admin_notice()
	 */
	public function test_no_admin_notice_1() {
		add_filter( 'unsplash_api_credentials', [ $this, 'disable_unsplash_api_credentials' ] );
		wp_set_current_user( self::$subscriber_id );
		set_current_screen( 'post.php' );
		$plugin = get_plugin_instance();
		$this->assertFalse( $plugin->admin_notice() );
		remove_filter( 'unsplash_api_credentials', [ $this, 'disable_unsplash_api_credentials' ] );
	}

	/**
	 * Test for admin_notice()
	 *
	 * @see Plugin::admin_notice()
	 */
	public function test_no_admin_notice_2() {
		add_filter( 'unsplash_api_credentials', [ $this, 'disable_unsplash_api_credentials' ] );
		wp_set_current_user( self::$admin_id );
		set_current_screen( 'settings_page_unsplash' );
		$plugin = get_plugin_instance();
		$this->assertFalse( $plugin->admin_notice() );
		remove_filter( 'unsplash_api_credentials', [ $this, 'disable_unsplash_api_credentials' ] );
	}

	/**
	 * Test for admin_notice()
	 *
	 * @see Plugin::admin_notice()
	 */
	public function test_no_admin_notice_3() {
		wp_set_current_user( self::$admin_id );
		set_current_screen( 'post.php' );
		$plugin = get_plugin_instance();
		$this->assertFalse( $plugin->admin_notice() );
	}

	/**
	 * Disable unsplash api details.
	 *
	 * @param array $unused Unused variable.
	 *
	 * @return array
	 */
	public function disable_unsplash_api_credentials( $unused ) { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return [
			'applicationId' => '',
			'secret'        => '',
			'utmSource'     => '',
		];
	}
}
