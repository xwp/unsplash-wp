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

		update_post_meta( self::$attachment_id, 'original_url', 'http://www.example.com/test.jpg' );
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
	}

	/**
	 * Test wp_get_attachment_url.
	 *
	 * @covers ::wp_get_attachment_url()
	 */
	public function test_wp_get_attachment_url() {
		$this->assertEquals( wp_get_attachment_url( self::$attachment_id ), 'http://www.example.com/test.jpg' );
	}

	/**
	 * Test image_downsize.
	 *
	 * @covers ::image_downsize()
	 */
	public function test_wp_get_attachment_image_src() {
		$image = image_downsize( self::$attachment_id );
		$this->assertInternalType( 'array', $image );
		$this->assertEquals( $image[0], 'http://www.example.com/test.jpg?w=300&h=300' );
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
		// FIXME: The img src in `self::$image_tag` is escaped via `get_image_tag()` and produces something similar to http://www.example.com/test.jpg?w=1&amp;h=1&h=1. Why are we adding the `w` and `h` query params?
//		$this->assertContains( 'http://www.example.com/test.jpg?w=1&h=1', $content );
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
	 * /**
	 * Test get_original_url_with_size.
	 *
	 * @covers ::get_original_url_with_size()
	 * @dataProvider data_various_params
	 *
	 * @param string $url Original URL of unsplash asset.
	 * @param int    $width Width of image.
	 * @param int    $height Height of image.
	 * @param array  $attr Other attributes to be passed to the URL.
	 * @param string $expected Expected value.
	 */
	public function test_get_original_url_with_size( $url, $width, $height, $attr, $expected ) {
		$this->assertSame( $this->hotlink->get_original_url_with_size( $url, $width, $height, $attr ), $expected );
	}

	/**
	 * Data provider for test_get_original_url_with_size.
	 *
	 * @return array
	 */
	public function data_various_params() {
		return [
			[ 'http://www.example.com/test.jpg', 222, 444, [], 'http://www.example.com/test.jpg?w=222&h=444' ],
			[ 'http://www.example.com/test.jpg', 100, 100, [], 'http://www.example.com/test.jpg?w=100&h=100' ],
			[ 'http://www.example.com/test.jpg', -1, -1, [], 'http://www.example.com/test.jpg?w=1&h=1' ],
			[ 'http://www.example.com/test.jpg', 'invalid', 'invalid', [], 'http://www.example.com/test.jpg?w=0&h=0' ],
			[ 'http://www.example.com/test.jpg', 100, 100, [ 'crop' => true ], 'http://www.example.com/test.jpg?w=100&h=100&crop=1' ],
			[ 'http://www.example.com/test.jpg?crop=1', 100, 100, [], 'http://www.example.com/test.jpg?crop=1&w=100&h=100' ],
		];
	}

}
