<?php
/**
 * Tests for Hotlink class.
 *
 * @package Unsplash
 */

namespace Unsplash;

/**
 * Tests for the Hotlink class.
 *
 * @coversDefaultClass \Unsplash\Hotlink
 */
class Test_Hotlink extends \WP_UnitTestCase {

	/**
	 * Hotlink instance.
	 *
	 * @var Hotlink
	 */
	public $hotlink;

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
	 * Image tags.
	 *
	 * @var string
	 */
	protected static $image_tag;

	/**
	 * Setup before any tests are to be run for this class.
	 *
	 * @param object $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$test_file     = '/tmp/canola.jpg';
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
		self::$image_tag = get_image_tag( self::$attachment_id, 'alt', 'title', 'left' );
	}

	/**
	 * Setup.
	 *
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		$this->hotlink = get_plugin_instance()->hotlink;
	}


	/**
	 * Test init.
	 *
	 * @covers ::init()
	 */
	public function test_init() {
		$this->assertEquals( 10, has_filter( 'image_downsize', [ $this->hotlink, 'image_downsize' ] ) );
		$this->assertEquals( 10, has_filter( 'wp_get_attachment_url', [ $this->hotlink, 'wp_get_attachment_url' ] ) );
		$this->assertEquals( 99, has_filter( 'the_content', [ $this->hotlink, 'hotlink_images_in_content' ] ) );
		$this->assertEquals( 10, has_filter( 'get_image_tag', [ $this->hotlink, 'get_image_tag' ] ) );
		$this->assertEquals( 99, has_filter( 'content_save_pre', [ $this->hotlink, 'replace_hotlinked_images_in_content' ] ) );
	}

	/**
	 * Test wp_get_attachment_url.
	 *
	 * @covers ::wp_get_attachment_url()
	 */
	public function test_wp_get_attachment_url() {
		$this->assertEquals( wp_get_attachment_url( self::$attachment_id ), 'https://images.unsplash.com/test.jpg' );
	}

	/**
	 * Test image_downsize.
	 *
	 * @covers ::image_downsize()
	 */
	public function test_wp_get_attachment_image_src() {
		$image = image_downsize( self::$attachment_id );
		$this->assertInternalType( 'array', $image );
		$this->assertEquals( $image[0], 'https://images.unsplash.com/test.jpg?w=300&h=300' );
	}

	/**
	 * Data for test_get_attachments_from_content
	 *
	 * @return array
	 */
	public function data_get_content() {
		return [
			'empty'                     => [ '', [] ],
			'no_img_tags'               => [ '<p>Hello world.</p>', [] ],
			'non_wp__img_tag'           => [ '<img class="foo" src="bar.jpg" />', [] ],
			'img_tag_with_src'          => [
				'<img class="wp-image-1" src="bar.jpg" />',
				[
					[
						'tag' => '<img class="wp-image-1" src="bar.jpg" />',
						'url' => 'bar.jpg',
						'id'  => 1,
					],
				],
			],
			'multiple_img_tag_with_src' => [
				'<img class="wp-image-1" src="bar.jpg" /><img class="wp-image-2" src="baz.jpg" />',
				[
					[
						'tag' => '<img class="wp-image-1" src="bar.jpg" />',
						'url' => 'bar.jpg',
						'id'  => 1,
					],
					[
						'tag' => '<img class="wp-image-2" src="baz.jpg" />',
						'url' => 'baz.jpg',
						'id'  => 2,
					],
				],
			],
		];
	}

	/**
	 * Test get_attachments_from_content()
	 *
	 * @dataProvider data_get_content
	 * @covers ::get_attachments_from_content()
	 *
	 * @param string $content Content.
	 * @param array  $expected Expected result.
	 */
	public function test_get_attachments_from_content( $content, $expected ) {
		$actual = $this->hotlink->get_attachments_from_content( $content );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test replace_hotlinked_images_in_content()
	 *
	 * @covers ::replace_hotlinked_images_in_content()
	 * @covers ::get_attachment_url()
	 */
	public function test_replace_hotlinked_images_in_content() {
		$wp_id = $this->factory->attachment->create_object(
			'melon.jpg',
			0,
			[
				'post_mime_type' => 'image/jpeg',
				'post_excerpt'   => 'A sample caption',
			]
		);

		$wp_img = get_image_tag( $wp_id, 'alt', 'title', 'left' );

		$test_page = self::factory()->post->create(
			[
				'post_type'    => 'page',
				'post_title'   => 'About',
				'post_status'  => 'publish',
				'post_content' => sprintf( 'Unsplash: %s, WordPress: %s', self::$image_tag, $wp_img ),
			]
		);

		$post    = get_post( $test_page );
		$content = $this->hotlink->replace_hotlinked_images_in_content( $post->post_content );
		$this->assertNotContains( 'https://images.unsplash.com', $content );
		$this->assertContains( 'http://example.org/wp-content/uploads//tmp/canola.jpg', $content );
		$this->assertContains( 'http://example.org/wp-content/uploads/melon.jpg', $content );
	}

	/**
	 * Data for test_get_image_size_from_url.
	 *
	 * @return array
	 */
	public function data_get_image_size_from_url() {
		return [
			'empty_string'      => [
				'',
				[
					0,
					0,
				],
			],
			'no_query'          => [
				'http://example.org',
				[
					0,
					0,
				],
			],
			'no_width'          => [
				'http://example.org/?h=100',
				[
					0,
					100,
				],
			],
			'no_height'         => [
				'http://example.org/?w=100',
				[
					100,
					0,
				],
			],
			'width_and_height'  => [
				'http://example.org/?w=100&h=200',
				[
					100,
					200,
				],
			],
			'escaped_ampersand' => [
				'http://example.org/?w=100&amp;h=200&amp;foo=bar&buzz',
				[
					100,
					200,
				],
			],
		];
	}

	/**
	 * Test get_image_size_from_url()
	 *
	 * @dataProvider data_get_image_size_from_url
	 * @covers ::get_image_size_from_url
	 *
	 * @param string $url URL.
	 * @param array  $expected Expected result.
	 */
	public function test_get_image_size_from_url( $url, $expected ) {
		$actual = $this->hotlink->get_image_size_from_url( $url );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test replace image in content.
	 *
	 * @covers ::hotlink_images_in_content()
	 * @covers ::replace_image()
	 * @covers ::get_image_size()
	 * @covers ::get_attachments_from_content()
	 */
	public function test_the_content() {
		$second_id  = $this->factory->attachment->create_object(
			'/tmp/melon.jpg',
			0,
			[
				'post_mime_type' => 'image/jpeg',
				'post_excerpt'   => 'A sample caption 2',
			]
		);
		$normal_img = get_image_tag( $second_id, 'alt', 'title', 'left' );

		$test_page = self::factory()->post->create(
			[
				'post_type'    => 'page',
				'post_title'   => 'About',
				'post_status'  => 'publish',
				'post_content' => sprintf( 'This is a %s image %s', self::$image_tag, $normal_img ),
			]
		);

		$post    = get_post( $test_page );
		$content = $this->hotlink->hotlink_images_in_content( $post->post_content );
		$this->assertContains( 'src="https://images.unsplash.com/test.jpg?w=300&h=300"', $content );
		$this->assertContains( '/tmp/melon.jpg', $content );
	}

	/**
	 * Test prime_post_caches.
	 *
	 * @covers ::prime_post_caches()
	 */
	public function test_prime_post_caches() {
		$attachment_ids     = [ self::$attachment_id, 99 ];
		$primed_attachments = $this->hotlink->prime_post_caches( $attachment_ids );
		$this->assertEqualSets( $primed_attachments, [ get_post( self::$attachment_id ) ] );
	}

	/**
	 * Test render_block.
	 *
	 * @covers ::render_block()
	 */
	public function test_render_block() {
		if ( ! function_exists( 'do_blocks' ) ) {
			$this->markTestSkipped( 'No do_blocks' );
		}
		$content   = sprintf(
			'<!-- wp:cover {"url":"https://localhost:8088/example.jpg","id":%d} -->
			<div class="wp-block-cover has-background-dim" style="background-image:url(https://localhost:8088/example.jpg)"><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
			<p class="has-text-align-center has-large-font-size"></p>
			<!-- /wp:paragraph --></div></div>
			<!-- /wp:cover -->',
			self::$attachment_id
		);
		$test_page = self::factory()->post->create(
			[
				'post_type'    => 'page',
				'post_title'   => 'Test page',
				'post_status'  => 'publish',
				'post_content' => $content,
			]
		);

		$post    = get_post( $test_page );
		$content = apply_filters( 'the_content', $post->post_content );
		$this->assertContains( 'https://images.unsplash.com/test.jpg', $content );
	}

	/**
	 * Test wp_prepare_attachment_for_js.
	 *
	 * @covers ::wp_prepare_attachment_for_js()
	 */
	public function test_no_wp_prepare_attachment_for_js_1() {
		$data   = [ 'foo' => 'bar' ];
		$result = $this->hotlink->wp_prepare_attachment_for_js( $data, false );
		$this->assertEqualSets( $result, $data );
	}

	/**
	 * Test wp_prepare_attachment_for_js.
	 *
	 * @covers ::wp_prepare_attachment_for_js()
	 */
	public function test_no_wp_prepare_attachment_for_js_2() {
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
		$result    = $this->hotlink->wp_prepare_attachment_for_js( $data, $image );
		$this->assertEqualSets( $result, $data );
	}

	/**
	 * Test wp_prepare_attachment_for_js.
	 *
	 * @covers ::wp_prepare_attachment_for_js()
	 * @covers ::get_attachment_url()
	 * @covers ::change_full_url()
	 */
	public function test_wp_prepare_attachment_for_js() {
		$image    = get_post( self::$attachment_id );
		$photo    = [
			'width'  => 999,
			'height' => 999,
			'urls'   => [ 'raw' => 'https://images.unsplash.com/test.jpg' ],
		];
		$result   = $this->hotlink->wp_prepare_attachment_for_js( $photo, $image );
		$plugin   = new Plugin();
		$expected = $plugin->add_image_sizes( $photo['urls']['raw'], $photo['width'], $photo['height'] );
		$url      = $this->hotlink->get_attachment_url( self::$attachment_id );
		$expected = $this->hotlink->change_full_url( $expected, 'url', $url );
		$this->assertEqualSets( $result['sizes'], $expected );
	}


		/**
		 * Test rest_prepare_attachment.
		 *
		 * @covers ::rest_prepare_attachment()
		 */
	public function test_no_rest_prepare_attachment() {
		$data    = [ 'foo' => 'bar' ];
		$reponse = new \WP_REST_Response( $data );
		$result  = $this->hotlink->rest_prepare_attachment( $reponse, false );
		$this->assertEqualSets( $result->get_data(), $reponse->get_data() );
	}

		/**
		 * Test rest_prepare_attachment.
		 *
		 * @covers ::rest_prepare_attachment()
		 */
	public function test_no_rest_prepare_attachment_1() {
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
		$reponse   = new \WP_REST_Response( $data );
		$result    = $this->hotlink->rest_prepare_attachment( $reponse, $image );
		$this->assertEqualSets( $result->get_data(), $reponse->get_data() );
	}

		/**
		 * Test rest_prepare_attachment.
		 *
		 * @covers ::rest_prepare_attachment()
		 * @covers ::change_fields()
		 * @covers ::get_attachment_url()
		 * @covers ::change_full_url()
		 */
	public function test_rest_prepare_attachment_2() {
		$image    = get_post( self::$attachment_id );
		$photo    = [
			'urls'                => [ 'raw' => 'https://images.unsplash.com/test.jpg' ],
			'media_details'       => [
				'width'  => 999,
				'height' => 999,
				'file'   => 'test.jpg',
			],
			'missing_image_sizes' => [
				'large',
			],
		];
		$reponse  = new \WP_REST_Response( $photo );
		$result   = $this->hotlink->rest_prepare_attachment( $reponse, $image );
		$plugin   = new Plugin();
		$sizes    = $plugin->add_image_sizes( $photo['urls']['raw'], $photo['media_details']['width'], $photo['media_details']['height'] );
		$expected = $this->hotlink->change_fields( $sizes, $photo['media_details']['file'] );
		$url      = $this->hotlink->get_attachment_url( self::$attachment_id );
		$expected = $this->hotlink->change_full_url( $expected, 'source_url', $url );
		$data     = $result->get_data();

		$this->assertEqualSets( $data['media_details']['sizes'], $expected );
		$this->assertEqualSets( $data['missing_image_sizes'], [] );
	}

	/**
	 * Test rest_prepare_attachment.
	 *
	 * @covers ::rest_prepare_attachment()
	 */
	public function test_rest_prepare_attachment_3() {
		$image   = get_post( self::$attachment_id );
		$photo   = [

			'urls'          => [ 'raw' => 'https://images.unsplash.com/nothing.jpg' ],
			'media_details' => [
				'width'  => 999,
				'height' => 999,
				'file'   => 'test.jpg',
			],
			'source_url'    => 'http://unsplash.com/test',
		];
		$reponse = new \WP_REST_Response( $photo );
		$result  = $this->hotlink->rest_prepare_attachment( $reponse, $image );
		$data    = $result->get_data();
		$this->assertEquals( $data['source_url'], 'http://example.org/wp-content/uploads//tmp/canola.jpg' );
	}

	/**
	 * Test wp_calculate_image_srcset.
	 *
	 * @covers ::wp_calculate_image_srcset()
	 */
	public function test_wp_calculate_image_srcset() {
		$result   = $this->hotlink->wp_calculate_image_srcset(
			[],
			[],
			'',
			[
				'width'  => 2000,
				'height' => 2000,
				'sizes'  => [
					'large' => [
						'width'  => 300,
						'height' => 9999,
					],
				],
			],
			self::$attachment_id
		);
		$expected = [
			[
				'url'        => 'https://images.unsplash.com/test.jpg?w=300&h=300&fm=jpg&q=85&fit=crop',
				'descriptor' => 'w',
				'value'      => 300,
			],
		];

		$this->assertEqualSets( $expected, array_values( $result ) );
	}

	/**
	 * Test wp_calculate_image_srcset.
	 *
	 * @covers ::wp_calculate_image_srcset()
	 */
	public function test_no_wp_calculate_image_srcset() {
		$second_id = $this->factory->attachment->create_object(
			'/tmp/melon.jpg',
			0,
			[
				'post_mime_type' => 'image/jpeg',
				'post_excerpt'   => 'A sample caption 2',
			]
		);

		$result = $this->hotlink->wp_calculate_image_srcset(
			[ 'foo' => 'bar' ],
			[],
			'',
			[
				'width'  => 2000,
				'height' => 2000,
			],
			$second_id
		);
		$this->assertEqualSets( [ 'foo' => 'bar' ], $result );
	}

	/**
	 * Test wp_get_attachment_caption.
	 *
	 * @covers ::wp_get_attachment_caption()
	 */
	public function test_wp_get_attachment_caption() {
		$caption = 'Hello <a href="#">there</a>!';
		$result  = $this->hotlink->wp_get_attachment_caption( $caption, self::$attachment_id );
		$this->assertEquals( $result, 'Hello there!' );
	}

	/**
	 * Test wp_get_attachment_caption.
	 *
	 * @covers ::wp_get_attachment_caption()
	 */
	public function test_no_wp_get_attachment_caption() {
		$second_id = $this->factory->attachment->create_object(
			'/tmp/melon.jpg',
			0,
			[
				'post_mime_type' => 'image/jpeg',
				'post_excerpt'   => 'A sample caption 2',
			]
		);
		$caption   = 'Hello <a href="#">there</a>!';
		$result    = $this->hotlink->wp_get_attachment_caption( $caption, $second_id );
		$this->assertEquals( $caption, $result );
	}


	/**
	 * Test wp_get_original_image_url.
	 *
	 * @covers ::wp_get_original_image_url()
	 */
	public function test_wp_get_original_image_url() {
		$result = $this->hotlink->wp_get_original_image_url( '', self::$attachment_id );
		$this->assertEquals( $result, 'https://www.unsplash.com/foo' );
	}

	/**
	 * Test wp_get_original_image_url.
	 *
	 * @covers ::wp_get_original_image_url()
	 */
	public function test_no_wp_get_original_image_url() {
		$second_id = $this->factory->attachment->create_object(
			'/tmp/melon.jpg',
			0,
			[
				'post_mime_type' => 'image/jpeg',
				'post_excerpt'   => 'A sample caption 2',
			]
		);
		$result    = $this->hotlink->wp_get_original_image_url( 'https://www.example.com/', $second_id );
		$this->assertEquals( 'https://www.example.com/', $result );
	}
}
