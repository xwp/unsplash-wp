<?php
/**
 * Tests for Import class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

use Mockery;
use WP_Mock;

/**
 * Test the WordPress import abstraction.
 */
class TestImport extends TestCase {
	/**
	 * Test get attachment.
	 *
	 * @covers \XWP\Unsplash\Import::__construct()
	 * @covers \XWP\Unsplash\Import::test_get_attachment()
	 */
	public function test_get_attachment() {
		WP_Mock::userFunction( 'wp_list_pluck' )->once()->andReturn( [] );
		WP_Mock::passthruFunction( 'current_time' );
		WP_Mock::userFunction( 'get_page_by_path' )->once()->andReturn( [ 'ID' => 123 ] );
		$import = new Import( 'eOvv4N6yNmk' );
		$this->assertEquals( $import->get_attachment(), 123 );

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
		WP_Mock::passthruFunction( 'wp_insert_attachment' );
		WP_Mock::passthruFunction( 'wp_slash' );
		Mockery::mock( 'WP_Error' );
		$file   = [
			'file' => true,
			'url'  => 'http://www.example.com/test.jpg',
		];
		$import = new Import(
			'eOvv4N6yNmk',
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
			]
		);

		$attachment = $import->create_attachment( $file );
		$this->assertTrue( is_array( $attachment ) );
		$this->assertArrayHasKey( 'post_name', $attachment );
		$this->assertArrayHasKey( 'post_mime_type', $attachment );
		$this->assertArrayHasKey( 'guid', $attachment );
		$this->assertSame( $attachment['post_name'], 'eOvv4N6yNmk' );
		$this->assertSame( $attachment['guid'], 'http://www.example.com/test.jpg' );
		$this->assertSame( $attachment['post_mime_type'], $import::MIME );
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
		$import     = new Import(
			'eOvv4N6yNmk',
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
			]
		);
		$attachment = $import->create_attachment( $file );
		$this->assertTrue( is_object( $attachment ) );
		$this->assertTrue( is_a( $attachment, 'WP_Error' ) );

	}

	/**
	 * Test pass WP_error to create_attachment.
	 *
	 * @covers \XWP\Unsplash\Import::__construct()
	 * @covers \XWP\Unsplash\Import::create_attachment()
	 */
	public function test_wp_error_create_attachment() {
		$file = Mockery::mock( 'WP_Error' );
		WP_Mock::userFunction( 'is_wp_error' )->once()->andReturn( false );
		$import     = new Import(
			'eOvv4N6yNmk',
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
			]
		);
		$attachment = $import->create_attachment( $file );
		$this->assertTrue( is_object( $attachment ) );
		$this->assertTrue( is_a( $attachment, 'WP_Error' ) );
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
		WP_Mock::userFunction( 'import_url' )->once()->with( 'http://www.example.com/test.jpg' )->andReturn( '' );
		WP_Mock::userFunction( 'wp_handle_upload' )->once()->andReturn( $file );
		$import = new Import(
			'eOvv4N6yNmk',
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
			],
			'http://www.example.com/test.jpg'
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
		$file  = [
			'file' => true,
			'url'  => 'http://www.example.com/test.jpg',
		];
		$error = Mockery::mock( 'WP_Error' );
		WP_Mock::userFunction( 'import_url' )->once()->with( 'http://www.example.com/test.jpg' )->andReturn( $error );
		WP_Mock::userFunction( 'wp_handle_upload' )->once()->andReturn( $file );
		$import = new Import(
			'eOvv4N6yNmk',
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
			],
			'http://www.example.com/test.jpg'
		);

		$attachment = $import->import_image();
		$this->assertTrue( is_array( $attachment ) );
		$this->assertSame( $attachment, $file );
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
		WP_Mock::userFunction( 'wp_set_object_terms' )->once()->andReturn( [ 1234 ] );

		$import = new Import(
			'eOvv4N6yNmk',
			[
				'user' => [
					'id'   => 'eOvv4N6yNmk',
					'name' => 'John Smith',
					'bio'  => 'I am a photographer.',
				],
			]
		);
		$user   = $import->process_user();
		$this->assertTrue( is_array( $user ) );
		$this->assertSame( $user, [ 1234 ] );
	}
}
