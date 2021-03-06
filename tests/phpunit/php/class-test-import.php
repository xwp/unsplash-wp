<?php
/**
 * Tests for Import class.
 *
 * @package Unsplash
 */

namespace Unsplash;

use WP_Error;
use WP_Query;

/**
 * Test the Import class.
 */
class Test_Import extends \WP_UnitTestCase {

	/**
	 * Test get attachment.
	 *
	 * @covers \Unsplash\Import::__construct()
	 * @covers \Unsplash\Import::process()
	 * @covers \Unsplash\Import::get_attachment_id()
	 */
	public function test_get_attachment() {
		$image  = new Image(
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
		$file   = [
			'file' => true,
			'url'  => 'http://www.example.com/test.jpg',
		];

		$attachment_id = $import->create_attachment( $file );

		$this->assertEquals( $import->get_attachment_id(), $attachment_id );
	}

	/**
	 * Test create attachment.
	 *
	 * @covers \Unsplash\Import::create_attachment()
	 */
	public function test_create_attachment() {
		$file   = [
			'file' => true,
			'url'  => 'http://www.example.com/test.jpg',
		];
		$image  = new Image(
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

		$attachment_id = $import->create_attachment( $file );
		$actual_id     = get_page_by_path( 'eOvv4N6yNmk', ARRAY_A, 'attachment' )['ID'];

		$this->assertEquals( $attachment_id, $actual_id );
	}

	/**
	 * Test Process meta attachment.
	 *
	 * @covers \Unsplash\Import::create_attachment()
	 * @covers \Unsplash\Import::process_meta()
	 * @covers \Unsplash\Import::process_tags()
	 * @covers \Unsplash\Import::process_source()
	 */
	public function test_process_meta() {
		$file   = [
			'file' => true,
			'url'  => 'http://www.example.com/test.jpg',
		];
		$image  = new Image(
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [
					[
						'title' => 'wibble',
					],
					[
						'title' => 'dibble',
					],
				],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
			]
		);
		$import = new Import(
			'eOvv4N6yNmk',
			$image
		);

		$attachment_id = $import->create_attachment( $file );
		$actual_id     = get_page_by_path( 'eOvv4N6yNmk', ARRAY_A, 'attachment' )['ID'];

		$this->assertEquals( $attachment_id, $actual_id );
		$import->process_meta();
		$map = [
			'color'                        => 'color',
			'original_id'                  => 'original_id',
			'original_url'                 => 'original_url',
			'original_link'                => 'original_link',
			'unsplash_location'            => 'unsplash_location',
			'unsplash_sponsor'             => 'unsplash_sponsor',
			'unsplash_exif'                => 'unsplash_exif',
			'_wp_attachment_metadata'      => 'meta',
			'unsplash_attachment_metadata' => 'meta',
			'_wp_attachment_image_alt'     => 'alt',
		];
		foreach ( $map as $key => $value ) {
			$this->assertEquals( $image->get_field( $value ), get_post_meta( $attachment_id, $key, true ) );
		}
		$import->process_tags();
		$tags     = wp_get_post_terms( $attachment_id, 'media_tag' );
		$tag_name = wp_list_pluck( $tags, 'name' );
		$this->assertEqualSets( [ 'dibble', 'wibble' ], $tag_name );
		$import->process_source();
		$tags     = wp_get_post_terms( $attachment_id, 'media_source' );
		$tag_name = wp_list_pluck( $tags, 'name' );
		$this->assertEqualSets( [ 'Unsplash' ], $tag_name );
	}

	/**
	 * Test pass empty array to create_attachment.
	 *
	 * @covers \Unsplash\Import::create_attachment()
	 */
	public function test_invalid_create_attachment() {
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
		$this->assertInternalType( 'object', $attachment );
		$this->assertInstanceOf( WP_Error::class, $attachment );

	}

	/**
	 * Test pass WP_error to create_attachment.
	 *
	 * @covers \Unsplash\Import::create_attachment()
	 */
	public function test_wp_error_create_attachment() {
		$file       = new WP_Error( 'testing' );
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
		$this->assertInternalType( 'object', $attachment );
		$this->assertEquals( $attachment, $file );
		$this->assertInstanceOf( WP_Error::class, $attachment );
	}
	/**
	 * Test pass WP_error to create_attachment.
	 *
	 * @covers \Unsplash\Import::create_attachment()
	 */
	public function test_wp_error_wp_insert_attachment() {
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
		$attachment = $import->create_attachment( new WP_Error() );
		$this->assertInternalType( 'object', $attachment );
		$this->assertInstanceOf( WP_Error::class, $attachment );
	}

	/**
	 * Test import image.
	 *
	 * @covers \Unsplash\Import::import_image()
	 */
	public function test_import_image() {
		$image  = new Image(
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
				'urls'            => [
					'full' => 'https://images.unsplash.com/photo-1552667466-07770ae110d0?ixlib=rb-1.2.1&q=85&fm=jpg&crop=entropy&cs=srgb&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
				],
			]
		);
		$import = new Import(
			'eOvv4N6yNmk',
			$image
		);

		$attachment = $import->import_image();
		$this->assertInternalType( 'array', $attachment );
		$this->assertEquals( [ 'file', 'url', 'type' ], array_keys( $attachment ) );
	}

	/**
	 * Test import image on multisite.
	 *
	 * @covers \Unsplash\Import::import_image()
	 */
	public function test_import_image_multisite() {
		$this->skipWithoutMultisite();

		// TODO: Refactor test to make multisite compatible.
		$file   = [
			'file' => true,
			'url'  => 'http://www.example.com/test.jpg',
		];
		$image  = new Image(
			[
				'id'              => 'eOvv4N6yNmk',
				'tags'            => [],
				'description'     => 'test description',
				'alt_description' => 'test alt description',
				'urls'            => [
					'full' => 'https://images.unsplash.com/photo-1552667466-07770ae110d0?ixlib=rb-1.2.1&q=85&fm=jpg&crop=entropy&cs=srgb&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
				],
			]
		);
		$import = new Import(
			'eOvv4N6yNmk',
			$image
		);

		$attachment = $import->import_image();
		$this->assertInternalType( 'array', $attachment );
		$this->assertSame( $attachment, $file );
	}

	/**
	 * Test invalid import image.
	 *
	 * @covers \Unsplash\Import::import_image()
	 */
	public function test_invalid_import_image() {
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
		$this->assertInternalType( 'object', $attachment );
		$this->assertInstanceOf( WP_Error::class, $attachment );
	}

	/**
	 * Test invalid handle upload.
	 *
	 * @covers \Unsplash\Import::import_image()
	 */
	public function test_invalid_handle_upload_import_image() {
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
		$this->assertInternalType( 'object', $attachment );
		$this->assertInstanceOf( WP_Error::class, $attachment );
	}

	/**
	 * Test process user.
	 *
	 * @covers \Unsplash\Import::process_user()
	 */
	public function test_process_user() {
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
		$file   = [
			'file' => true,
			'url'  => 'http://www.example.com/test.jpg',
		];

		$import->create_attachment( $file );
		$term_taxonomy_ids = $import->process_user();
		$actual_ids        = wp_list_pluck( wp_get_post_terms( $import->get_attachment_id(), 'unsplash_user' ), 'term_taxonomy_id' );

		$this->assertInternalType( 'array', $term_taxonomy_ids );

		// Map the string IDs to integers so they can be compared.
		$term_taxonomy_ids = array_map(
			static function ( $id ) {
				return (int) $id;
			},
			$term_taxonomy_ids
		);

		$this->assertSame( $term_taxonomy_ids, $actual_ids );
	}

	/**
	 * Test process.
	 *
	 * @covers \Unsplash\Import::process()
	 * @covers \Unsplash\Import::get_attachment_id()
	 */
	public function test_process() {
		$image  = new Image(
			[
				'id'   => 'processed_id',
				'urls' => [
					'full' => 'https://images.unsplash.com/photo-1552667466-07770ae110d0?ixlib=rb-1.2.1&q=85&fm=jpg&crop=entropy&cs=srgb&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
				],
				'user' => [
					'id'   => 'eOvv4N6yNmk',
					'name' => 'John Smith',
					'bio'  => 'I am a photographer.',
				],
			]
		);
		$import = new Import(
			'processed_id',
			$image
		);

		$attachment_id = $import->process();
		$parsed_args   = [
			'post_type'              => 'attachment',
			'name'                   => 'processed_id',
			'fields'                 => 'ids',
			'order'                  => 'DESC',
			'suppress_filters'       => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'lazy_load_term_meta'    => false,
			'no_found_rows'          => true,
			'posts_per_page'         => 1,
		];
		$get_posts     = new WP_Query();
		$attachments   = $get_posts->query( $parsed_args );

		$actual_id = ! empty( $attachments ) ? array_shift( $attachments ) : false;

		$this->assertEquals( $attachment_id, $actual_id );
	}

	/**
	 * Test get_attachment_id with no results.
	 *
	 * @covers \Unsplash\Import::get_attachment_id()
	 */
	public function test_get_no_attachment_id() {
		$image  = new Image(
			[
				'id'   => 'test_get_no_attachment_id',
				'urls' => [
					'full' => 'https://images.unsplash.com/photo-1552667466-07770ae110d0?ixlib=rb-1.2.1&q=85&fm=jpg&crop=entropy&cs=srgb&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
				],
				'user' => [
					'id'   => 'eOvv4N6yNmk',
					'name' => 'John Smith',
					'bio'  => 'I am a photographer.',
				],
			]
		);
		$import = new Import(
			'test_get_no_attachment_id',
			$image
		);

		$attachment_id = $import->get_attachment_id();
		$this->assertEquals( $attachment_id, false );
	}
}
