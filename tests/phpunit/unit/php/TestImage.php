<?php
/**
 * Tests for Plugin class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

use WP_Mock;

/**
 * Test the WordPress plugin abstraction.
 */
class TestImage extends TestCase {
	/**
	 * Get example data.
	 *
	 * @return array
	 */
	protected function get_data() {
		$test_data = [
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
				'sponsor' =>
					[
						'id' => 'XnhDFu3Jr-A',
					],
			],
			'user'            =>
				[
					'id'         => 'XnhDFu3Jr-A',
					'updated_at' => '2020-02-12T14:11:09-05:00',
					'username'   => 'harleydavidson',
					'name'       => 'Harley-Davidson',
					'first_name' => 'Harley-Davidson',
				],
			'exif'            =>
				[
					'make'          => 'Canon',
					'model'         => 'Canon EOS 5D Mark IV',
					'exposure_time' => '1/500',
					'aperture'      => '4.5',
					'focal_length'  => '23.0',
					'iso'           => 100,
				],
			'tags'            =>
				[
					'type'  => 'landing_page',
					'title' => 'motorcycle',
				],
		];

		return $test_data;
	}

	/**
	 * Test process data.
	 *
	 * @covers \XWP\Unsplash\Image::__construct()
	 * @covers \XWP\Unsplash\Image::process_fields()
	 * @covers \XWP\Unsplash\Image::get_field()
	 * @covers \XWP\Unsplash\Image::get_image_url()
	 */
	public function test_process_data() {
		WP_Mock::userFunction( 'wp_list_pluck' )->once()->andReturn( [] );
		WP_Mock::userFunction( 'current_time' )->once()->andReturn( '123456' );
		$test_data = $this->get_data();
		$image     = new Image( $test_data );
		$this->assertSame( $image->get_field( 'original_id' ), $test_data['id'] );
		$this->assertSame( $image->get_field( 'description' ), $test_data['description'] );
		$this->assertSame( $image->get_field( 'alt' ), $test_data['alt_description'] );
		$this->assertSame( $image->get_field( 'original_url' ), $image->get_image_url( 'raw' ) );
	}

	/**
	 * Test image url.
	 *
	 * @covers \XWP\Unsplash\Image::__construct()
	 * @covers \XWP\Unsplash\Image::get_image_url()
	 */
	public function test_get_image_url() {
		WP_Mock::userFunction( 'wp_list_pluck' )->once()->andReturn( [] );
		WP_Mock::userFunction( 'current_time' )->once()->andReturn( '123456' );
		$image = new Image( $this->get_data() );
		$this->assertSame( '', $image->get_image_url( 'invalid' ) );
	}

	/**
	 * Test field.
	 *
	 * @covers \XWP\Unsplash\Image::__construct()
	 * @covers \XWP\Unsplash\Image::get_field()
	 */
	public function test_get_field() {
		WP_Mock::userFunction( 'wp_list_pluck' )->once()->andReturn( [] );
		WP_Mock::userFunction( 'current_time' )->once()->andReturn( '123456' );
		$image = new Image( $this->get_data() );
		$this->assertSame( '', $image->get_field( 'invalid' ) );
	}

}
