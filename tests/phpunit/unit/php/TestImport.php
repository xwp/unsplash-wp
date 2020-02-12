<?php
/**
 * Tests for Import class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

use Mockery;
use WP_Mock;
use WP_Error;

/**
 * Test the WordPress import abstraction.
 */
class TestImport extends TestCase {
	/**
	 * Test get attachment.
	 *
	 * @covers \XWP\Unsplash\Import::__construct()
	 * @covers \XWP\Unsplash\Import::get_attachment_id()
	 */
	public function test_get_attachment() {
		WP_Mock::userFunction( 'wp_list_pluck' )->once()->andReturn( [] );
		WP_Mock::passthruFunction( 'current_time' );
		WP_Mock::passthruFunction( 'wp_slash' );
		WP_Mock::userFunction( 'get_page_by_path' )->once()->andReturn( [ 'ID' => 123 ] );
		$image = new Image(
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
			]
		);

		$import = new Import(
			'eOvv4N6yNmk',
			$image
		);
		$this->assertEquals( $import->get_attachment_id(), 123 );

	}

	/**
	 * Test get attachment.
	 *
	 * @covers \XWP\Unsplash\Import::__construct()
	 * @covers \XWP\Unsplash\Import::create_attachment()
	 */
	public function test_create_attachment() {

		WP_Mock::userFunction( 'wp_list_pluck' )->once()->andReturn( [] );
		WP_Mock::userFunction( 'is_wp_error' )->twice()->andReturn( false );

		WP_Mock::passthruFunction( 'current_time' );
		Mockery::mock( 'WP_Error' );
		$file            = [
			'file' => true,
			'url'  => 'http://www.example.com/test.jpg',
		];
		$image           = new Image(
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
			]
		);
		$test_attachment = [
			'post_name'      => 'eOvv4N6yNmk',
			'guid'           => 'http://www.example.com/test.jpg',
			'post_mime_type' => $image::MIME,
			'post_content'   => 'test description',
			'post_title'     => 'test alt description',
		];
		WP_Mock::userFunction( 'wp_insert_attachment' )->once()->andReturn( $test_attachment );
		$import = new Import(
			'eOvv4N6yNmk',
			$image
		);

		$attachment = $import->create_attachment( $file );
		$this->assertTrue( is_array( $attachment ) );
		$this->assertArrayHasKey( 'post_name', $attachment );
		$this->assertArrayHasKey( 'post_mime_type', $attachment );
		$this->assertArrayHasKey( 'guid', $attachment );
		$this->assertSame( $attachment['post_name'], 'eOvv4N6yNmk' );
		$this->assertSame( $attachment['guid'], 'http://www.example.com/test.jpg' );
		$this->assertSame( $attachment['post_mime_type'], $image::MIME );
		$this->assertSame( $attachment['post_content'], 'test description' );
		$this->assertSame( $attachment['post_title'], 'test alt description' );
	}

	/**
	 * Test pass empty array to create_attachment.
	 *
	 * @covers \XWP\Unsplash\Import::__construct()
	 * @covers \XWP\Unsplash\Import::create_attachment()
	 */
	public function test_invalid_create_attachment() {
		Mockery::mock( 'WP_Error' );
		$file       = [];
		$image      = new Image(
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
			]
		);
		$import     = new Import(
			'eOvv4N6yNmk',
			$image
		);
		$attachment = $import->create_attachment( $file );
		$this->assertTrue( is_object( $attachment ) );
		$this->assertInstanceOf( WP_Error::class, $attachment );

	}

	/**
	 * Test pass WP_error to create_attachment.
	 *
	 * @covers \XWP\Unsplash\Import::__construct()
	 * @covers \XWP\Unsplash\Import::create_attachment()
	 */
	public function test_wp_error_create_attachment() {
		$file = new WP_Error( 'testing' );
		WP_Mock::userFunction( 'is_wp_error' );
		$image      = new Image(
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
			]
		);
		$import     = new Import(
			'eOvv4N6yNmk',
			$image
		);
		$attachment = $import->create_attachment( $file );
		$this->assertTrue( is_object( $attachment ) );
		$this->assertEquals( $attachment, $file );
		$this->assertInstanceOf( WP_Error::class, $attachment );
	}
	/**
	 * Test pass WP_error to create_attachment.
	 *
	 * @covers \XWP\Unsplash\Import::__construct()
	 * @covers \XWP\Unsplash\Import::create_attachment()
	 */
	public function test_wp_error_wp_insert_attachment() {
		$file  = [
			'file' => true,
			'url'  => 'http://www.example.com/test.jpg',
		];
		$error = new WP_Error( 'testing' );
		WP_Mock::userFunction( 'is_wp_error' );
		WP_Mock::userFunction( 'wp_insert_attachment' )->once()->andReturn( $error );
		$image      = new Image(
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
			]
		);
		$import     = new Import(
			'eOvv4N6yNmk',
			$image
		);
		$attachment = $import->create_attachment( $file );
		$this->assertTrue( is_object( $attachment ) );
		$this->assertInstanceOf( WP_Error::class, $attachment );
	}

	/**
	 * Test get attachment.
	 *
	 * @covers \XWP\Unsplash\Import::__construct()
	 * @covers \XWP\Unsplash\Import::import_image()
	 */
	public function test_import_image() {
		$file = [
			'file' => true,
			'url'  => 'http://www.example.com/test.jpg',
		];
		WP_Mock::userFunction( 'download_url' )->once()->with( 'http://www.example.com/test.jpg' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_handle_upload' )->once()->andReturn( $file );
		WP_Mock::userFunction( 'is_multisite' )->once()->andReturn( false );
		$image  = new Image(
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
				'urls'            => [
					'full' => 'http://www.example.com/test.jpg',
				],
			]
		);
		$import = new Import(
			'eOvv4N6yNmk',
			$image
		);

		$attachment = $import->import_image();
		$this->assertTrue( is_array( $attachment ) );
		$this->assertSame( $attachment, $file );
	}

	/**
	 * Test get attachment.
	 *
	 * @covers \XWP\Unsplash\Import::import_image()
	 * @covers \XWP\Unsplash\Import::check_upload_size()
	 */
	public function test_import_image_multisite() {
		$file = [
			'file' => true,
			'url'  => 'http://www.example.com/test.jpg',
		];
		WP_Mock::userFunction( 'download_url' )->once()->with( 'http://www.example.com/test.jpg' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_handle_upload' )->once()->andReturn( $file );
		WP_Mock::userFunction( 'is_multisite' )->once()->andReturn( true );
		WP_Mock::userFunction( 'get_site_option' )->once()->with( 'upload_space_check_disabled' )->andReturn( false );
		WP_Mock::userFunction( 'get_site_option' )->once()->with( 'fileupload_maxk', 1500 )->andReturn( 1500 );
		WP_Mock::userFunction( 'get_upload_space_available' )->once()->andReturn( 100 );
		WP_Mock::userFunction( 'upload_is_user_over_quota' )->once()->andReturn( false );
		$image  = new Image(
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
				'urls'            => [
					'full' => 'http://www.example.com/test.jpg',
				],
			]
		);
		$import = new Import(
			'eOvv4N6yNmk',
			$image
		);

		$attachment = $import->import_image();
		$this->assertTrue( is_array( $attachment ) );
		$this->assertSame( $attachment, $file );
	}

	/**
	 * Test get attachment.
	 *
	 * @covers \XWP\Unsplash\Import::__construct()
	 * @covers \XWP\Unsplash\Import::import_image()
	 */
	public function test_invalid_import_image() {
		$error = new WP_Error( 'invalid_file' );
		WP_Mock::userFunction( 'download_url' )->once()->with( 'http://www.example.com/test.jpg' )->andReturn( $error );
		WP_Mock::passthruFunction( 'is_wp_error' );
		$image  = new Image(
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
				'urls'            => [
					'full' => 'http://www.example.com/test.jpg',
				],
			]
		);
		$import = new Import(
			'eOvv4N6yNmk',
			$image
		);

		$attachment = $import->import_image();
		$this->assertTrue( is_object( $attachment ) );
		$this->assertInstanceOf( WP_Error::class, $attachment );
	}

	/**
	 * Test get attachment.
	 *
	 * @covers \XWP\Unsplash\Import::__construct()
	 * @covers \XWP\Unsplash\Import::import_image()
	 */
	public function test_invalid_handle_upload_import_image() {
		$file = [
			'error' => 'something went wrong',
		];
		WP_Mock::userFunction( 'download_url' )->once()->with( 'http://www.example.com/test.jpg' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_handle_upload' )->once()->andReturn( $file );
		WP_Mock::userFunction( 'is_multisite' )->once()->andReturn( false );
		$image  = new Image(
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
				'urls'            => [
					'full' => 'http://www.example.com/test.jpg',
				],
			]
		);
		$import = new Import(
			'eOvv4N6yNmk',
			$image
		);

		$attachment = $import->import_image();
		$this->assertTrue( is_object( $attachment ) );
		$this->assertInstanceOf( WP_Error::class, $attachment );
	}

	/**
	 * Test process user
	 *
	 * @covers \XWP\Unsplash\Import::__construct()
	 * @covers \XWP\Unsplash\Import::process_user()
	 */
	public function test_process_user() {
		WP_Mock::userFunction( 'get_term_by' )->once()->andReturn( false );
		WP_Mock::userFunction( 'wp_insert_term' )->once()->andReturn( [ 'term_id' => 1234 ] );
		WP_Mock::userFunction( 'get_term' )->once()->with( 1234, 'unsplash_user' )->andReturn( (object) [ 'term_id' => 1234 ] );
		WP_Mock::userFunction( 'add_term_meta' )->once();
		WP_Mock::userFunction( 'wp_set_post_terms' )->once()->andReturn( [ 1234 ] );
		$image  = new Image(
			[
				'user' => [
					'id'   => 'eOvv4N6yNmk',
					'name' => 'John Smith',
					'bio'  => 'I am a photographer.',
				],
			]
		);
		$import = new Import(
			'eOvv4N6yNmk',
			$image
		);
		$user   = $import->process_user();
		$this->assertTrue( is_array( $user ) );
		$this->assertSame( $user, [ 1234 ] );
	}

	/**
	 * Test process user
	 *
	 * @covers \XWP\Unsplash\Import::__construct()
	 * @covers \XWP\Unsplash\Import::process()
	 * @covers \XWP\Unsplash\Import::process_user()
	 * @covers \XWP\Unsplash\Import::process_source()
	 * @covers \XWP\Unsplash\Import::process_tags()
	 */
	public function test_process() {
		$file = [
			'file' => true,
			'url'  => 'http://www.example.com/test.jpg',
		];
		WP_Mock::userFunction( 'get_page_by_path' )->once()->andReturn( false );
		WP_Mock::userFunction( 'update_post_meta' )->times( 8 );
		WP_Mock::userFunction( 'wp_set_post_terms' )->times( 3 );
		WP_Mock::userFunction( 'get_term_by' )->once()->andReturn( false );
		WP_Mock::userFunction( 'wp_insert_term' )->once()->andReturn( [ 'term_id' => 1234 ] );
		WP_Mock::userFunction( 'get_term' )->once()->with( 1234, 'unsplash_user' )->andReturn( (object) [ 'term_id' => 1234 ] );
		WP_Mock::userFunction( 'add_term_meta' )->once();
		WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		WP_Mock::userFunction( 'download_url' )->once()->andReturn( $file );
		WP_Mock::userFunction( 'wp_handle_upload' )->once()->andReturn( $file );

		$image = new Image(
			[
				'user' => [
					'id'   => 'eOvv4N6yNmk',
					'name' => 'John Smith',
					'bio'  => 'I am a photographer.',
				],
			]
		);

		WP_Mock::userFunction( 'wp_insert_attachment' )->once()->andReturn( 543 );
		$import       = new Import(
			'eOvv4N6yNmk',
			$image
		);
		$return_value = $import->process();
		$this->assertSame( $return_value, 543 );
	}
}
