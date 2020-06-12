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
	 * Test file.
	 *
	 * @var string
	 */
	protected static $test_file;

	/**
	 * Generated attachment ID.
	 *
	 * @var int
	 */
	protected static $attachment_id;

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

		self::$test_file     = 'canola.jpg';
		self::$attachment_id = $factory->attachment->create_object(
			self::$test_file,
			0,
			[
				'post_mime_type' => 'image/jpeg',
				'post_excerpt'   => 'A sample caption',
			]
		);

		update_post_meta( self::$attachment_id, 'original_url', 'https://images.unsplash.com/test.jpg' );
		update_post_meta( self::$attachment_id, 'original_link', 'https://www.unsplash.com/foo' );
		update_post_meta(
			self::$attachment_id,
			'unsplash_attachment_metadata',
			[
				'image_meta' => [
					'created_timestamp' => '2020-05-27T03:12:33-04:00',
				],
			]
		);

		// Add Unsplash user term and term_meta.
		$term_id = $factory->term->create(
			array(
				'taxonomy' => 'unsplash_user',
				'name'     => 'Example User',
				'slug'     => 'example-user',
			)
		);

		add_term_meta(
			$term_id,
			'unsplash_meta',
			[
				'name'     => 'Example User',
				'username' => 'example_user',
				'links'    => [
					'html' => 'https://unsplash.com/@example_user',
				],
			]
		);

		wp_set_object_terms( self::$attachment_id, [ $term_id ], 'unsplash_user' );
	}

	/**
	 * Test constructor.
	 *
	 * @see Plugin::__construct()
	 */
	public function test_construct() {
		$plugin = new Plugin();
		$this->assertEquals( 10, has_action( 'plugins_loaded', [ $plugin, 'init' ] ) );
		$this->assertEquals( 10, has_action( 'wp_default_scripts', [ $plugin, 'register_polyfill_scripts' ] ) );
		$this->assertEquals( 10, has_action( 'wp_enqueue_media', [ $plugin, 'enqueue_media_scripts' ] ) );
		$this->assertEquals( 10, has_action( 'admin_enqueue_scripts', [ $plugin, 'enqueue_admin_scripts' ] ) );
		$this->assertEquals( 10, has_action( 'init', [ $plugin, 'register_taxonomy' ] ) );
		$this->assertEquals( 10, has_action( 'init', [ $plugin, 'register_meta' ] ) );
		$this->assertEquals( 10, has_filter( 'wp_prepare_attachment_for_js', [ $plugin, 'add_unsplash_author_meta' ] ) );
		$this->assertEquals( 10, has_action( 'print_media_templates', [ $plugin, 'add_media_templates' ] ) );
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
		$this->assertInstanceOf( Block_Type::class, $plugin->block_type );
		$this->assertEquals( true, has_filter( 'plugin_action_links_' . $plugin->file, [ $plugin, 'action_links' ] ) );
	}

	/**
	 * Test for register_polyfill_scripts() method.
	 *
	 * @see Plugin::register_polyfill_scripts()
	 */
	public function test_register_polyfill_scripts() {
		if ( version_compare( '5.0', get_bloginfo( 'version' ), '>' ) ) {
			get_plugin_instance()->register_polyfill_scripts( wp_scripts() );
		}

		$expected_handles = [
			'wp-i18n',
			'wp-polyfill',
			'wp-url',
			'lodash',
		];

		foreach ( $expected_handles as $expected_handle ) {
			$this->assertTrue( wp_script_is( $expected_handle, 'registered' ) );
		}
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
	 * Test for test_enqueue_admin_scripts() method.
	 *
	 * @see Plugin::test_enqueue_admin_scripts()
	 */
	public function test_no_enqueue_admin_scripts() {
		set_current_screen( 'post.php' );
		$plugin = get_plugin_instance();
		$plugin->enqueue_admin_scripts();
		$this->assertFalse( wp_script_is( 'unsplash-media-library-js', 'enqueued' ) );
	}

	/**
	 * Test for test_enqueue_admin_scripts() method.
	 *
	 * @see Plugin::test_enqueue_admin_scripts()
	 */
	public function test_enqueue_admin_scripts() {
		set_current_screen( 'upload.php' );
		$plugin = get_plugin_instance();
		$plugin->enqueue_admin_scripts();
		$this->assertTrue( wp_style_is( 'unsplash-admin-style', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'unsplash-media-library-js', 'enqueued' ) );
	}

	/**
	 * Test for enqueue_media_scripts() method doesn't load on other pages.
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
	 * Test for enqueue_media_scripts() method doesn't load on widget
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
			'user'            => [
				'name'     => 'Example User',
				'username' => 'example_user',
				'links'    => [
					'html' => 'https://unsplash.com/@example_user',
				],
			],
		];
		$output = $plugin->wp_prepare_attachment_for_js( $image );
		$this->assertEquals( $output['id'], $image['id'] );
		$this->assertEquals( $output['alt'], $image['alt_description'] );
		$this->assertEquals( $output['description'], $image['description'] );
		$this->assertEquals( $output['sizes']['full']['height'], $image['height'] );
		$this->assertEquals( $output['sizes']['full']['width'], $image['width'] );
		$this->assertEquals( $output['sizes']['full']['url'], $image['urls']['raw'] );
		$this->assertEquals( $output['sizes']['thumbnail']['url'], 'http://www.example.com/test.jpg?fm=jpg&q=85&fit=crop&w=150&h=40' );
		$this->assertEquals( $output['sizes']['medium_large']['url'], 'http://www.example.com/test.jpg?fm=jpg&q=85&fit=crop&w=768&h=207' );
		$this->assertEquals( $output['icon'], 'http://www.example.com/thumb.jpg?fm=jpg&q=85&fit=crop&w=150&h=150' );
		$this->assertEquals( 'Example User', $output['author'] );
		$this->assertEquals( 'https://unsplash.com/@example_user', $output['unsplashAuthorLink'] );
	}

	/**
	 * Test for image_sizes()
	 *
	 * @see Plugin::image_sizes()
	 */
	public function test_no_image_sizes() {
		$plugin   = get_plugin_instance();
		$expected = [ 'large', 'medium', 'medium_large', 'thumbnail' ];
		add_filter( 'intermediate_image_sizes', '__return_empty_array' );
		$this->assertEqualSets( array_keys( $plugin->image_sizes() ), $expected );
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
			[ 'http://www.example.com/test.jpg', 100, 100, [ 'crop' => 0 ], 'http://www.example.com/test.jpg?fm=jpg&q=85&fit=crop&crop=0&w=100&h=100' ],
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
		$plugin->admin_notice();
		$output = ob_get_clean();
		$this->assertContains( 'To complete setup of the Unsplash plugin you will need to add the API access key.', $output );
		remove_filter( 'unsplash_api_credentials', [ $this, 'disable_unsplash_api_credentials' ] );
	}

	/**
	 * Test for admin_notice()
	 *
	 * @see Plugin::admin_notice()
	 */
	public function test_admin_notice_connection() {
		add_filter( 'unsplash_api_credentials', [ $this, 'invalid_unsplash_api_credentials' ] );
		wp_set_current_user( self::$admin_id );
		set_current_screen( 'post.php' );
		$plugin = get_plugin_instance();
		ob_start();
		$plugin->admin_notice();
		$output = ob_get_clean();
		$this->assertContains( 'The Unsplash API credentials supplied are not authorized.Please visit the Unsplash settings page to reconnect to Unsplash now.', wp_strip_all_tags( $output ) );
		remove_filter( 'unsplash_api_credentials', [ $this, 'invalid_unsplash_api_credentials' ] );
	}

	/**
	 * Test for admin_notice()
	 *
	 * @see Plugin::admin_notice()
	 */
	public function test_admin_notice_no_manage_options_perms() {
		wp_set_current_user( self::$subscriber_id );
		set_current_screen( 'post.php' );
		$plugin = get_plugin_instance();
		$this->assertFalse( $plugin->admin_notice() );
	}

	/**
	 * Test for admin_notice()
	 *
	 * @see Plugin::admin_notice()
	 */
	public function test_no_admin_notice_no_screen_object() {
		wp_set_current_user( self::$admin_id );
		$plugin = get_plugin_instance();
		$this->assertFalse( $plugin->admin_notice() );
	}

	/**
	 * Test for admin_notice()
	 *
	 * @see Plugin::admin_notice()
	 */
	public function test_admin_notice_not_on_settings_page() {
		wp_set_current_user( self::$admin_id );
		set_current_screen( 'settings_page_unsplash' );
		$plugin = get_plugin_instance();
		$this->assertFalse( $plugin->admin_notice() );
	}

	/**
	 * Test for admin_notice()
	 *
	 * @see Plugin::admin_notice()
	 */
	public function test_admin_notice_has_credentials() {
		wp_set_current_user( self::$admin_id );
		set_current_screen( 'post.php' );
		$plugin = get_plugin_instance();
		$this->assertFalse( $plugin->admin_notice() );
	}

	/**
	 * Test for action_links()
	 *
	 * @see Plugin::action_links()
	 */
	public function test_action_links() {
		$plugin = get_plugin_instance();
		$links  = $plugin->action_links( [] );

		$this->assertEquals( '<a href="http://example.org/wp-admin/options-general.php?page=unsplash">Settings</a>', array_pop( $links ) );
	}

	/**
	 * Test add_unsplash_author_meta.
	 *
	 * @covers ::add_unsplash_author_meta()
	 */
	public function test_no_add_unsplash_author_meta_1() {
		$plugin = get_plugin_instance();
		$data   = [ 'foo' => 'bar' ];
		$result = $plugin->add_unsplash_author_meta( $data, false );
		$this->assertEqualSets( $result, $data );
	}

	/**
	 * Test add_unsplash_author_meta.
	 *
	 * @covers ::add_unsplash_author_meta()
	 */
	public function test_no_add_unsplash_author_meta_2() {
		$plugin    = get_plugin_instance();
		$second_id = $this->factory->attachment->create_object(
			'/tmp/melon.jpg',
			0,
			[
				'post_mime_type' => 'image/jpeg',
				'post_excerpt'   => 'A sample caption 2',
			]
		);
		$image     = get_post( $second_id );
		$data      = [ 'foo' => 'bar' ];
		$result    = $plugin->add_unsplash_author_meta( $data, $image );
		$this->assertEqualSets( $result, $data );
	}

	/**
	 * Test add_unsplash_author_meta.
	 *
	 * @covers ::add_unsplash_author_meta()
	 */
	public function test_add_unsplash_author_meta() {
		$plugin = get_plugin_instance();
		$image  = get_post( self::$attachment_id );
		$photo  = [
			'width'  => 999,
			'height' => 999,
			'urls'   => [ 'raw' => 'https://images.unsplash.com/test.jpg' ],
		];
		$result = $plugin->add_unsplash_author_meta( $photo, $image );

		$this->assertEquals( 'Example User', $result['unsplashAuthor'] );
		$this->assertEquals( 'https://unsplash.com/@example_user', $result['unsplashAuthorLink'] );
		$this->assertEquals( 'May 27, 2020', $result['unsplashCreatedAt'] );
	}

	/**
	 * Test for add_media_templates()
	 *
	 * @see Plugin::add_media_templates()
	 */
	public function test_add_media_templates() {
		$plugin = get_plugin_instance();
		ob_start();
		$plugin->add_media_templates();
		$output = ob_get_clean();

		$this->assertContains( 'tmpl-unsplash-attachment-details-two-column', $output );
		$this->assertContains( 'Attachment Preview', $output );
		$this->assertContains( 'Photo by', $output );
		$this->assertContains( 'data.unsplashAuthorLink', $output );
		$this->assertContains( 'Date:', $output );
		$this->assertContains( 'data.unsplashCreatedAt', $output );

		$this->assertContains( 'Attachment Details', $output );
		$this->assertContains( '(opens in a new tab)', $output );
		$this->assertContains( 'pixels', $output );
		$this->assertContains( 'tmpl-unsplash-attachment-details', $output );
	}

	/**
	 * Disable Unsplash api details.
	 *
	 * @return array
	 */
	public function disable_unsplash_api_credentials() {
		return [
			'applicationId' => '',
			'secret'        => '',
			'utmSource'     => '',
		];
	}

	/**
	 * Invalid Unsplash api details.
	 *
	 * @return array
	 */
	public function invalid_unsplash_api_credentials() {
		return [
			'applicationId' => 'foo-bar',
			'secret'        => '',
			'utmSource'     => '',
		];
	}
}
