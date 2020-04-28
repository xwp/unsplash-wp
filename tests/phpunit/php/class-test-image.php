<?php
/**
 * Tests for Image class.
 *
 * @package Unsplash
 */

namespace Unsplash;

/**
 * Test the Image class.
 */
class Test_Image extends \WP_UnitTestCase {
	/**
	 * Get example data.
	 *
	 * @return array
	 */
	protected function get_data() {
		return [
			'id'              => 'xAHtaYIHlPI',
			'created_at'      => '2019-05-27T14:11:35-04:00',
			'updated_at'      => '2020-02-07T00:02:32-05:00',
			'promoted_at'     => null,
			'width'           => 8192,
			'height'          => 5462,
			'color'           => '#F9FBFC',
			'description'     => 'man',
			'alt_description' => 'man riding touring motorcycle during daytime',
			'urls'            => [
				'raw'     => 'https://images.unsplash.com/photo-1558980664-3a031cf67ea8?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9',
				'full'    => 'https://images.unsplash.com/photo-1558980664-3a031cf67ea8?ixlib=rb-1.2.1&q=85&fm=jpg&crop=entropy&cs=srgb&ixid=eyJhcHBfaWQiOjEyMDd9',
				'regular' => 'https://images.unsplash.com/photo-1558980664-3a031cf67ea8?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=1080&fit=max&ixid=eyJhcHBfaWQiOjEyMDd9',
				'small'   => 'https://images.unsplash.com/photo-1558980664-3a031cf67ea8?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=400&fit=max&ixid=eyJhcHBfaWQiOjEyMDd9',
				'thumb'   => 'https://images.unsplash.com/photo-1558980664-3a031cf67ea8?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=200&fit=max&ixid=eyJhcHBfaWQiOjEyMDd9',
			],
			'sponsorship'     => [
				'sponsor' => [
					'id' => 'XnhDFu3Jr-A',
				],
			],
			'user'            => [
				'id'         => 'XnhDFu3Jr-A',
				'updated_at' => '2020-02-12T14:11:09-05:00',
				'username'   => 'harleydavidson',
				'name'       => 'Harley-Davidson',
				'first_name' => 'Harley-Davidson',
				'links'      => [
					'html' => 'https://www.unpslash.com/harleydavidson',
				],
			],
			'exif'            => [
				'make'          => 'Canon',
				'model'         => 'Canon EOS 5D Mark IV',
				'exposure_time' => '1/500',
				'aperture'      => '4.5',
				'focal_length'  => '23.0',
				'iso'           => 100,
			],
			'tags'            => [
				[
					'type'  => 'landing_page',
					'title' => 'motorcycle',
				],
			],
		];
	}

	/**
	 * Test process data.
	 *
	 * @covers \Unsplash\Image::__construct()
	 * @covers \Unsplash\Image::process_fields()
	 * @covers \Unsplash\Image::get_field()
	 * @covers \Unsplash\Image::get_image_url()
	 */
	public function test_process_data() {
		$test_data = $this->get_data();
		$image     = new Image( $test_data );
		$this->assertSame( $image->get_field( 'original_id' ), strtolower( $test_data['id'] ) );
		$this->assertSame( $image->get_field( 'description' ), $test_data['description'] );
		$this->assertSame( $image->get_field( 'alt' ), $test_data['alt_description'] );
		$this->assertSame( $image->get_field( 'original_url' ), $image->get_image_url( 'raw' ) );
	}

	/**
	 * Test image url.
	 *
	 * @covers \Unsplash\Image::__construct()
	 * @covers \Unsplash\Image::get_image_url()
	 */
	public function test_get_image_url() {
		$image = new Image( $this->get_data() );
		$this->assertSame( '', $image->get_image_url( 'invalid' ) );
	}

	/**
	 * Test field.
	 *
	 * @covers \Unsplash\Image::__construct()
	 * @covers \Unsplash\Image::get_field()
	 */
	public function test_get_field() {
		$image = new Image( $this->get_data() );
		$this->assertSame( '', $image->get_field( 'invalid' ) );
	}

	/**
	 * Test image field.
	 *
	 * @covers \Unsplash\Image::__construct()
	 * @covers \Unsplash\Image::get_image_field()
	 */
	public function test_get_image_field() {
		$image = new Image( $this->get_data() );
		$this->assertSame( '', $image->get_image_field( 'invalid' ) );
	}

	/**
	 * Test get captionl.
	 *
	 * @covers \Unsplash\Image::__construct()
	 * @covers \Unsplash\Image::get_caption()
	 */
	public function test_get_caption() {
		$image = new Image( $this->get_data(), 'test_utm_source' );
		$this->assertRegexp( '/Harley-Davidson/', $image->get_caption() );
		$this->assertRegexp( '/https:\/\/unsplash.com/', $image->get_caption() );
		$this->assertRegexp( '/https:\/\/www.unpslash.com\/harleydavidson/', $image->get_caption() );
		$this->assertContains( 'test_utm_source', $image->get_caption() );
	}
	/**
	 * Test get captionl.
	 *
	 * @covers \Unsplash\Image::__construct()
	 * @covers \Unsplash\Image::get_caption()
	 */
	public function test_no_get_caption() {
		$image = new Image( [], 'test_utm_source' );
		$this->assertSame( '', $image->get_caption() );
	}

	/**
	 * Test get captionl.
	 *
	 * @covers \Unsplash\Image::__construct()
	 * @covers \Unsplash\Image::get_caption()
	 */
	public function test_no_get_caption_1() {
		$image = new Image( [], 'test_utm_source' );
		$this->assertSame( '', $image->get_caption() );
	}

	/**
	 * Test get captionl.
	 *
	 * @covers \Unsplash\Image::__construct()
	 * @covers \Unsplash\Image::get_caption()
	 */
	public function test_no_get_caption_2() {
		$image = new Image(
			[
				'user' => [
					'name'  => '',
					'links' => [
						'html' => '',
					],
				],
			],
			'test_utm_source'
		);
		$this->assertSame( '', $image->get_caption() );
	}


}
