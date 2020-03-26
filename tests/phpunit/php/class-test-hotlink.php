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
		self::$image_tag = get_image_tag( self::$attachment_id, 'alt', 'title', 'left' );
	}

	/**
	 * Setup.
	 *
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		$this->hotlink = new Hotlink( new Plugin() );
		$this->hotlink->init();
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
	 * Test replace image in content.
	 *
	 * @covers ::hotlink_images_in_content()
	 * @covers ::replace_image()
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
		$content = apply_filters( 'the_content', $post->post_content );
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
		$this->assertEquals( $data['source_url'], 'https://images.unsplash.com/test.jpg' );
	}

}
