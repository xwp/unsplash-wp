<?php
/**
 * Tests for REST API Controller.
 *
 * @package Unsplash
 */

namespace Unsplash;

use WP_REST_Request;
use WP_Test_REST_Controller_Testcase;

/**
 * Tests for the Rest_Controller class.
 */
class Test_Rest_Controller extends WP_Test_REST_Controller_Testcase {

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
	 * List of registered routes.
	 *
	 * @var array[]
	 */
	private static $routes;

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

		static::$routes = rest_get_server()->get_routes();
	}

	/**
	 * Remove fake data.
	 */
	public static function wpTearDownAfterClass() {
		self::delete_user( self::$admin_id );
		self::delete_user( self::$subscriber_id );
	}

	/**
	 * Test register_routes().
	 *
	 * @covers \Unsplash\Rest_Controller::register_routes()
	 */
	public function test_register_routes() {
		$this->assertArrayHasKey( $this->get_route(), static::$routes );
		$this->assertCount( 1, static::$routes[ $this->get_route() ] );

		$this->assertArrayHasKey( $this->get_route( '/(?P<id>[\w-]+)' ), static::$routes );
		$this->assertCount( 1, static::$routes[ $this->get_route( '/(?P<id>[\w-]+)' ) ] );

		$this->assertArrayHasKey( $this->get_route( '/import/(?P<id>[\w-]+)' ), static::$routes );
		$this->assertCount( 1, static::$routes[ $this->get_route( '/import/(?P<id>[\w-]+)' ) ] );

		$this->assertArrayHasKey( $this->get_route( '/search' ), static::$routes );
		$this->assertCount( 1, static::$routes[ $this->get_route( '/search' ) ] );
	}

	/**
	 * Test the context parameter of each route.
	 */
	public function test_context_param() {
		$this->markTestSkipped( 'Not implemented' );
	}

	/**
	 * Test get_items().
	 *
	 * @covers \Unsplash\Rest_Controller::get_items()
	 */
	public function test_get_items() {
		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', $this->get_route() );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		// Assert that 10 photos are returned.
		$this->assertCount( 10, $data );

		// Assert that each photo object has the attributes we would need.
		foreach ( $data as $photo_object ) {
			$expected_keys = [ 'id', 'created_at', 'updated_at', 'width', 'height', 'color', 'description', 'alt_description', 'urls' ];
			$this->assertEquals( $expected_keys, array_keys( $photo_object ) );
		}

		$header = $response->get_headers();
		$hit    = $header['X-WP-Unsplash-Cache-Hit'];
		$this->assertEquals( $hit, 0 );
	}

	/**
	 * Test get_items() with AJAX request.
	 *
	 * @covers \Unsplash\Rest_Controller::get_items()
	 */
	public function test_get_items_via_ajax() {
		wp_set_current_user( self::$admin_id );
		$request = new WP_REST_Request( 'GET', $this->get_route() );
		$request->set_header( 'X-Requested-With', 'XMLHttpRequest' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		// Assert that 10 photos are returned.
		$this->assertCount( 10, $data );

		// Assert that each photo object has the attributes we would need.
		foreach ( $data as $photo_object ) {
			$expected_keys = [
				'id',
				'unsplashId',
				'title',
				'filename',
				'url',
				'link',
				'alt',
				'author',
				'description',
				'caption',
				'name',
				'height',
				'width',
				'status',
				'uploadedTo',
				'date',
				'modified',
				'menuOrder',
				'mime',
				'type',
				'subtype',
				'icon',
				'dateFormatted',
				'nonces',
				'editLink',
				'meta',
				'sizes',
			];
			$this->assertEquals( $expected_keys, array_keys( $photo_object ) );
		}
	}

	/**
	 * Test prepare_item_for_response().
	 *
	 * @covers \Unsplash\Rest_Controller::prepare_item_for_response()
	 */
	public function test_prepare_item_for_response() {
		wp_set_current_user( self::$admin_id );
		$photo   = $this->get_photo_response();
		$request = new WP_REST_Request( 'GET', $this->get_route() );

		$expected = new \WP_REST_Response(
			[
				'id'              => 'rO8TdlRrOo0',
				'created_at'      => '2019-05-12T09:40:48-04:00',
				'updated_at'      => '2020-03-07T00:04:08-05:00',
				'width'           => 3998,
				'height'          => 2785,
				'color'           => '#F6F7FB',
				'description'     => '',
				'alt_description' => 'black wolf near rocks',
				'urls'            => [
					'raw'     => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
					'full'    => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&q=85&fm=jpg&crop=entropy&cs=srgb&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
					'regular' => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=1080&fit=max&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
					'small'   => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=400&fit=max&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
					'thumb'   => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=200&fit=max&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
				],
			]
		);

		$actual = get_plugin_instance()->rest_controller->prepare_item_for_response( $photo, $request );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test prepare_item_for_response() with AJAX request.
	 *
	 * @covers \Unsplash\Rest_Controller::prepare_item_for_response()
	 */
	public function test_prepare_item_for_response_for_ajax() {
		wp_set_current_user( self::$admin_id );
		$photo   = $this->get_photo_response();
		$request = new WP_REST_Request( 'GET', $this->get_route() );
		$request->set_header( 'X-Requested-With', 'XMLHttpRequest' );

		$expected = [
			'id'            => 'unsplash-0',
			'title'         => '',
			'filename'      => 'ro8tdlrroo0.jpeg',
			'url'           => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
			'link'          => 'https://unsplash.com/photos/rO8TdlRrOo0?utm_source=WordPress-XWP&utm_medium=referral&utm_campaign=api-credit',
			'alt'           => 'black wolf near rocks',
			'author'        => 'Waldemar Brandt',
			'description'   => 'black wolf near rocks',
			'name'          => 'ro8tdlrroo0',
			'height'        => 2785,
			'width'         => 3998,
			'status'        => 'inherit',
			'uploadedTo'    => 0,
			'date'          => 1557668448000,
			'modified'      => 1583557448000,
			'menuOrder'     => 0,
			'mime'          => 'image/jpeg',
			'type'          => 'image',
			'subtype'       => 'jpeg',
			'icon'          => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&q=85&fm=jpg&crop=entropy&cs=tinysrgb&w=150&fit=crop&ixid=eyJhcHBfaWQiOjEwMjU2NX0&h=150',
			'dateFormatted' => 'May 12, 2019',
			'nonces'        => [
				'update' => false,
				'delete' => false,
				'edit'   => false,
			],
			'editLink'      => false,
			'meta'          => false,
			'sizes'         => [
				'full'         => [
					'url'         => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
					'height'      => 2785,
					'width'       => 3998,
					'orientation' => 0,
				],
				'thumbnail'    => [
					'url'         => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEwMjU2NX0&w=150&h=104&fm=jpg&q=85&fit=crop',
					'height'      => 104,
					'width'       => 150,
					'orientation' => 0,
				],
				'medium'       => [
					'url'         => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEwMjU2NX0&w=400&h=278&fm=jpg&q=85&fit=crop',
					'height'      => 278,
					'width'       => 400,
					'orientation' => 0,
				],
				'medium_large' => [
					'url'         => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEwMjU2NX0&w=768&h=534&fm=jpg&q=85&fit=crop',
					'height'      => 534,
					'width'       => 768,
					'orientation' => 0,
				],
				'large'        => [
					'url'         => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEwMjU2NX0&w=1024&h=713&fm=jpg&q=85&fit=crop',
					'height'      => 713,
					'width'       => 1024,
					'orientation' => 0,
				],
			],
			'unsplashId'    => 'rO8TdlRrOo0',
		];

		if ( version_compare( '5.2', get_bloginfo( 'version' ), '<' ) ) {
			$expected['sizes']['1536x1536'] = [
				'url'         => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEwMjU2NX0&w=1536&h=1069&fm=jpg&q=85&fit=crop',
				'height'      => 1069,
				'width'       => 1536,
				'orientation' => 0,
			];

			$expected['sizes']['2048x2048'] = [
				'url'         => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEwMjU2NX0&w=2048&h=1426&fm=jpg&q=85&fit=crop',
				'height'      => 1426,
				'width'       => 2048,
				'orientation' => 0,
			];
		}

		$photo  = get_plugin_instance()->rest_controller->set_unique_media_id( $photo, 0, 1, 30 );
		$actual = get_plugin_instance()->rest_controller->prepare_item_for_response( $photo, $request );
		unset( $actual['caption'] );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test arguments for get_items().
	 */
	public function test_get_items_args() {
		$expected = [
			'context'  => [
				'description'       => 'Scope under which the request is made; determines fields present in response.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
				'enum'              => [ 'view', 'embed', 'edit' ],
				'default'           => 'view',
			],
			'page'     => [
				'description'       => 'Current page of the collection.',
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			],
			'per_page' => [
				'description'       => 'Maximum number of items to be returned in result set.',
				'type'              => 'integer',
				'default'           => 10,
				'maximum'           => 30,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			],
			'order_by' => [
				'description' => 'How to sort the photos.',
				'type'        => 'string',
				'default'     => 'latest',
				'enum'        => [ 'latest', 'oldest', 'popular' ],
			],
		];

		$this->assertEquals( $expected, static::$routes[ $this->get_route() ][0]['args'] );
	}

	/**
	 * Test get_item().
	 *
	 * @covers \Unsplash\Rest_Controller::get_item()
	 */
	public function test_get_item() {
		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', $this->get_route( '/uRuPYB0P8to' ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		// The `updated_at` value is expected to change frequently.
		unset( $data['updated_at'] );

		// The URL paths for each image type can change frequently, so instead test that the the expected image types are returned.
		$expected_url_types = [ 'raw', 'full', 'regular', 'small', 'thumb' ];
		$this->assertEquals( $expected_url_types, array_keys( $data['urls'] ) );
		unset( $data['urls'] );

		// Test the rest of the response data.
		$expected = [
			'id'              => 'uRuPYB0P8to',
			'created_at'      => '2019-05-27T14:23:58-04:00',
			'width'           => 4002,
			'height'          => 6000,
			'color'           => '#D9E8EF',
			'description'     => '',
			'alt_description' => 'black motorcycle',
		];

		$header = $response->get_headers();
		$hit    = $header['X-WP-Unsplash-Cache-Hit'];
		$this->assertEquals( $hit, 0 );

		$this->assertEquals( $expected, $data );

	}

	/**
	 * Test get_download().
	 *
	 * @covers \Unsplash\Rest_Controller::get_import()
	 */
	public function test_get_import() {
		wp_set_current_user( self::$admin_id );
		add_filter( 'upload_dir', [ $this, 'upload_dir_patch' ] );
		$request  = new WP_REST_Request( 'GET', $this->get_route( '/import/uRuPYB0P8to' ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		// The `updated_at` value is expected to change frequently.
		unset( $data['updated_at'] );
		// The URL paths for each image type can change frequently, so instead test that the the expected image types are returned.
		$expected_url_types = [ 'raw', 'full', 'regular', 'small', 'thumb' ];
		$this->assertEquals( $expected_url_types, array_keys( $data['urls'] ) );
		unset( $data['urls'] );

		$expected = [
			'id'              => 'uRuPYB0P8to',
			'created_at'      => '2019-05-27T14:23:58-04:00',
			'width'           => 4002,
			'height'          => 6000,
			'color'           => '#D9E8EF',
			'description'     => '',
			'alt_description' => 'black motorcycle',
		];

		$this->assertEquals( $expected, $data );
		$this->assertEquals( 301, $response->get_status() );
		remove_filter( 'upload_dir', [ $this, 'upload_dir_patch' ] );
	}

	/**
	 * Test post_process() auth.
	 *
	 * @covers \Unsplash\Rest_Controller::post_process()
	 * @covers \Unsplash\Rest_Controller::create_item_permissions_check()
	 */
	public function test_post_process() {
		add_filter( 'upload_dir', [ $this, 'upload_dir_patch' ] );
		$orig_file = DIR_TESTDATA . '/images/test-image.jpg';
		$test_file = get_temp_dir() . 'test-image.jpg';
		copy( $orig_file, $test_file );
		$second_id = $this->factory->attachment->create_object(
			$test_file,
			0,
			[
				'post_mime_type' => 'image/jpeg',
				'post_excerpt'   => 'A sample caption 2',
			]
		);
		update_post_meta(
			$second_id,
			'unsplash_attachment_metadata',
			[
				'width'      => 2,
				'foo'        => 'bar',
				'image_meta' => [ 'aperture' => 1 ],
				'sizes'      => null,
			]
		);
		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', $this->get_route( '/post-process/' . $second_id ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $response->get_data(), [ 'processed' => true ] );
		$meta = wp_get_attachment_metadata( $second_id );
		$this->assertArrayHasKey( 'foo', $meta );
		$this->assertArrayHasKey( 'sizes', $meta );
		$this->assertArrayHasKey( 'width', $meta );
		$this->assertSame( 50, $meta['width'] );
		$this->assertSame( [ 'aperture' => 1 ], $meta['image_meta'] );
		remove_filter( 'upload_dir', [ $this, 'upload_dir_patch' ] );
	}

	/**
	 * Test validate_get_attachment().
	 *
	 * @covers \Unsplash\Rest_Controller::post_process()
	 * @covers \Unsplash\Rest_Controller::validate_get_attachment()
	 */
	public function test_post_process_invalid() {
		$test_page = self::factory()->post->create(
			[
				'post_type'    => 'page',
				'post_title'   => 'About',
				'post_status'  => 'publish',
				'post_content' => 'hello there',
			]
		);

		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', $this->get_route( '/post-process/' . $test_page ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	/**
	 * Test validate_get_attachment().
	 *
	 * @covers \Unsplash\Rest_Controller::post_process()
	 * @covers \Unsplash\Rest_Controller::validate_get_attachment()
	 */
	public function test_post_process_invalid_2() {
		$test_page = wp_rand();

		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', $this->get_route( '/post-process/' . $test_page ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	/**
	 * Test get_item() auth.
	 *
	 * @covers \Unsplash\Rest_Controller::get_item()
	 * @covers \Unsplash\Rest_Controller::get_item_permissions_check()
	 */
	public function test_get_item_auth() {
		wp_set_current_user( self::$subscriber_id );
		$request  = new WP_REST_Request( 'GET', $this->get_route( '/uRuPYB0P8to' ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	/**
	 * Test get_items() auth.
	 *
	 * @covers \Unsplash\Rest_Controller::get_items()
	 * @covers \Unsplash\Rest_Controller::get_items_permissions_check()
	 */
	public function test_get_items_auth() {
		wp_set_current_user( self::$subscriber_id );
		$request  = new WP_REST_Request( 'GET', $this->get_route() );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	/**
	 * Test get_import() auth.
	 *
	 * @covers \Unsplash\Rest_Controller::get_import()
	 * @covers \Unsplash\Rest_Controller::create_item_permissions_check()
	 */
	public function test_get_import_auth() {
		wp_set_current_user( self::$subscriber_id );
		$request  = new WP_REST_Request( 'GET', $this->get_route( '/import/uRuPYB0P8to' ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_create', $response, 403 );
	}

	/**
	 * Test post_process() auth.
	 *
	 * @covers \Unsplash\Rest_Controller::post_process()
	 * @covers \Unsplash\Rest_Controller::create_item_permissions_check()
	 */
	public function test_post_process_auth() {
		$second_id = $this->factory->attachment->create_object(
			'/tmp/melon.jpg',
			0,
			[
				'post_mime_type' => 'image/jpeg',
				'post_excerpt'   => 'A sample caption 2',
			]
		);
		wp_set_current_user( self::$subscriber_id );
		$request  = new WP_REST_Request( 'GET', $this->get_route( '/post-process/' . $second_id ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_create', $response, 403 );
	}

	/**
	 * Test arguments for get_item().
	 */
	public function test_get_item_args() {
		$expected = [
			'id'      => [
				'description' => 'Unsplash image ID.',
				'type'        => 'string',
			],
			'context' => [
				'default'           => 'view',
				'enum'              => [ 'view', 'embed', 'edit' ],
				'description'       => 'Scope under which the request is made; determines fields present in response.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			],
		];

		$this->assertEquals( $expected, static::$routes[ $this->get_route( '/(?P<id>[\w-]+)' ) ][0]['args'] );
	}

	/**
	 * Test get_search().
	 *
	 * @covers \Unsplash\Rest_Controller::get_search()
	 */
	public function test_get_search() {
		wp_set_current_user( self::$admin_id );
		$request = new WP_REST_Request( 'GET', $this->get_route( '/search' ) );
		$request->set_param( 'search', 'motorcycle' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertCount( 10, $data );
		$expected_keys = [
			'id',
			'created_at',
			'updated_at',
			'width',
			'height',
			'color',
			'description',
			'alt_description',
			'urls',
		];
		foreach ( $data as $photo_data ) {
			foreach ( $expected_keys as $key ) {
				$this->assertArrayHasKey( $key, $photo_data );
			}
		}
		$header = $response->get_headers();
		$hit    = $header['X-WP-Unsplash-Cache-Hit'];
		$this->assertEquals( $hit, 0 );
	}

	/**
	 * Test get_search() with spaces.
	 *
	 * @covers \Unsplash\Rest_Controller::get_search()
	 */
	public function test_get_search_with_spaces() {
		wp_set_current_user( self::$admin_id );
		$request = new WP_REST_Request( 'GET', $this->get_route( '/search' ) );
		$request->set_param( 'search', 'star wars' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		$this->assertCount( 10, $data );
		$expected_keys = [
			'id',
			'created_at',
			'updated_at',
			'width',
			'height',
			'color',
			'description',
			'alt_description',
			'urls',
		];
		foreach ( $data as $photo_data ) {
			foreach ( $expected_keys as $key ) {
				$this->assertArrayHasKey( $key, $photo_data );
			}
		}
	}

	/**
	 * Data for the test `test_get_search_collections_param()`.
	 *
	 * @return array
	 */
	public function data_test_get_search() {
		return [
			'string arg'        => [
				'foobar',
				400,
			],
			'double comma'      => [
				'10,,20',
				400,
			],
			'trailing comma'    => [
				'10,20,',
				400,
			],
			'space between ids' => [
				'10, 20',
				400,
			],
			'untrimmed space'   => [
				'   10,20   ',
				400,
			],
			'one id'            => [
				'10',
				200,
			],
			'multiple ids'      => [
				'10,20',
				200,
			],
		];
	}

	/**
	 * Test `collections` parameter for `get_search()`.
	 *
	 * @dataProvider data_test_get_search
	 *
	 * @param string $query_param Query parameter.
	 * @param int    $status_code Expected status code.
	 */
	public function test_get_search_collections_param( $query_param, $status_code ) {
		wp_set_current_user( self::$admin_id );
		$request = new WP_REST_Request( 'GET', $this->get_route( '/search' ) );
		$request->set_query_params(
			[
				'search'      => 'motorcycle',
				'collections' => $query_param,
			]
		);
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( $status_code, $response->get_status() );
		if ( 400 === $status_code ) {
			$this->assertEquals( 'rest_invalid_param', $response->data['code'] );
		}
	}

	/**
	 * Test arguments for get_search().
	 */
	public function test_get_search_args() {
		$expected = [
			'context'     => [
				'default'           => 'view',
				'enum'              => [ 'view', 'embed', 'edit' ],
				'description'       => 'Scope under which the request is made; determines fields present in response.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			],
			'page'        => [
				'default'           => 1,
				'description'       => 'Current page of the collection.',
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			],
			'per_page'    => [
				'default'           => 10,
				'description'       => 'Maximum number of items to be returned in result set.',
				'type'              => 'integer',
				'maximum'           => 30,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			],
			'search'      => [
				'description' => 'Limit results to those matching a string.',
				'type'        => 'string',
				'required'    => true,
			],
			'orientation' => [
				'enum'        => [ 'landscape', 'portrait', 'squarish' ],
				'description' => 'Filter search results by photo orientation.',
				'type'        => 'string',
				'default'     => null,
			],
			'collections' => [
				'description'       => 'Collection ID(‘s) to narrow search. If multiple, comma-separated.',
				'type'              => 'string',
				'default'           => null,
				'validate_callback' => [ 'Unsplash\\Rest_Controller', 'validate_get_search_param' ],
			],
		];

		$this->assertEquals( $expected, static::$routes[ $this->get_route( '/search' ) ][0]['args'] );
	}

	/**
	 * Test create_item().
	 */
	public function test_create_item() {
		$this->markTestSkipped( 'Method not implemented' );
	}

	/**
	 * Test update_item().
	 */
	public function test_update_item() {
		$this->markTestSkipped( 'Method not implemented' );
	}

	/**
	 * Test delete_item().
	 */
	public function test_delete_item() {
		$this->markTestSkipped( 'Method not implemented' );
	}

	/**
	 * Test prepare_item().
	 */
	public function test_prepare_item() {
		$this->markTestSkipped( 'Method not implemented' );
	}

	/**
	 * Test get_item_schema().
	 *
	 * @covers \Unsplash\Rest_Controller::get_item_schema()
	 */
	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', $this->get_route() );
		$response   = rest_get_server()->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertCount( 9, $properties );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'created_at', $properties );
		$this->assertArrayHasKey( 'updated_at', $properties );
		$this->assertArrayHasKey( 'alt_description', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'color', $properties );
		$this->assertArrayHasKey( 'height', $properties );
		$this->assertArrayHasKey( 'width', $properties );
		$this->assertArrayHasKey( 'urls', $properties );
	}

	/**
	 * Callback to patch "basedir" when used in `wp_unique_filename()
	 *
	 * @param array $upload_dir Array of upload dir values.
	 *
	 * @return mixed
	 */
	public function upload_dir_patch( $upload_dir ) {
		$upload_dir['path'] = $upload_dir['basedir'];
		$upload_dir['url']  = $upload_dir['baseurl'];
		return $upload_dir;
	}

	/**
	 * Data provider for test_is_ajax_request.
	 *
	 * @return array
	 */
	public function data_test_is_ajax_request() {
		$normal_request = new WP_REST_Request();

		$ajax_request = new WP_REST_Request();
		$ajax_request->set_header( 'X-Requested-With', 'XMLHttpRequest' );

		return [
			[ $normal_request, false ],
			[ $ajax_request, true ],
		];
	}

	/**
	 * Test is_ajax_request().
	 *
	 * @dataProvider data_test_is_ajax_request
	 * @covers       \Unsplash\Rest_Controller::is_ajax_request()
	 *
	 * @param WP_REST_Request $request  Request.
	 * @param bool            $expected Expected.
	 */
	public function test_is_ajax_request( $request, $expected ) {
		$rest_controller = new Rest_Controller( new Plugin() );
		$actual          = $rest_controller->is_ajax_request( $request );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test set_unique_media_id().
	 *
	 * @covers \Unsplash\Rest_Controller::set_unique_media_id()
	 */
	public function test_set_unique_media_id() {
		$photo    = $this->get_photo_response();
		$index    = 3;
		$page     = 3;
		$per_page = 10;

		$rest_controller = new Rest_Controller( new Plugin() );
		$actual          = $rest_controller->set_unique_media_id( $photo, $index, $page, $per_page );
		$this->assertEquals( 'unsplash-23', $actual['id'] );
		$this->assertEquals( 'rO8TdlRrOo0', $actual['unsplash_id'] );
	}

	/**
	 * Generate a prefixed route path.
	 *
	 * @param string $path URL path.
	 * @return string Route path.
	 */
	private function get_route( $path = '' ) {
		return '/unsplash/v1/photos' . "$path";
	}

	/**
	 * Get a sample photo response.
	 *
	 * @return array
	 */
	private function get_photo_response() {
		return [
			'id'                       => 'rO8TdlRrOo0',
			'unsplash_id'              => 'unsplash-0',
			'created_at'               => '2019-05-12T09:40:48-04:00',
			'updated_at'               => '2020-03-07T00:04:08-05:00',
			'promoted_at'              => null,
			'width'                    => 3998,
			'height'                   => 2785,
			'color'                    => '#F6F7FB',
			'description'              => null,
			'alt_description'          => 'black wolf near rocks',
			'urls'                     => [
				'raw'     => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
				'full'    => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&q=85&fm=jpg&crop=entropy&cs=srgb&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
				'regular' => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=1080&fit=max&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
				'small'   => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=400&fit=max&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
				'thumb'   => 'https://images.unsplash.com/photo-1557668364-d0aa79a798f4?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=200&fit=max&ixid=eyJhcHBfaWQiOjEwMjU2NX0',
			],
			'links'                    => [
				'self'              => 'https://api.unsplash.com/photos/rO8TdlRrOo0',
				'html'              => 'https://unsplash.com/photos/rO8TdlRrOo0?utm_source=WordPress-XWP&utm_medium=referral&utm_campaign=api-credit',
				'download'          => 'https://unsplash.com/photos/rO8TdlRrOo0/download?utm_source=WordPress-XWP&utm_medium=referral&utm_campaign=api-credit',
				'download_location' => 'https://api.unsplash.com/photos/rO8TdlRrOo0/download',
			],
			'categories'               => [],
			'likes'                    => 16,
			'liked_by_user'            => false,
			'current_user_collections' => [],
			'user'                     => [
				'id'                 => 'FNxGZSkxTiM',
				'updated_at'         => '2020-03-10T00:32:39-04:00',
				'username'           => 'waldemarbrandt67w',
				'name'               => 'Waldemar Brandt',
				'first_name'         => 'Waldemar',
				'last_name'          => 'Brandt',
				'twitter_username'   => 'BrandtWaldemar',
				'portfolio_url'      => null,
				'bio'                => null,
				'location'           => 'Schleswig-Holstein , Germany',
				'links'              => [
					'self'      => 'https://api.unsplash.com/users/waldemarbrandt67w',
					'html'      => 'https://unsplash.com/@waldemarbrandt67w?utm_source=WordPress-XWP&utm_medium=referral&utm_campaign=api-credit',
					'photos'    => 'https://api.unsplash.com/users/waldemarbrandt67w/photos',
					'likes'     => 'https://api.unsplash.com/users/waldemarbrandt67w/likes',
					'portfolio' => 'https://api.unsplash.com/users/waldemarbrandt67w/portfolio',
					'following' => 'https://api.unsplash.com/users/waldemarbrandt67w/following',
					'followers' => 'https://api.unsplash.com/users/waldemarbrandt67w/followers',
				],
				'profile_image'      => [
					'small'  => 'https://images.unsplash.com/profile-1527426873441-dba4a87c4458?ixlib=rb-1.2.1&q=80&fm=jpg&crop=faces&cs=tinysrgb&fit=crop&h=32&w=32',
					'medium' => 'https://images.unsplash.com/profile-1527426873441-dba4a87c4458?ixlib=rb-1.2.1&q=80&fm=jpg&crop=faces&cs=tinysrgb&fit=crop&h=64&w=64',
					'large'  => 'https://images.unsplash.com/profile-1527426873441-dba4a87c4458?ixlib=rb-1.2.1&q=80&fm=jpg&crop=faces&cs=tinysrgb&fit=crop&h=128&w=128',
				],
				'instagram_username' => 'waldemarbrandt67',
				'total_collections'  => 1,
				'total_likes'        => 2780,
				'total_photos'       => 656,
				'accepted_tos'       => true,
			],
			'tags'                     => [
				[
					'type'   => 'landing_page',
					'title'  => 'animal',
					'source' => [
						'ancestry'         => [
							'type'     => [
								'slug'        => 'images',
								'pretty_slug' => 'Images',
							],
							'category' => [
								'slug'        => 'animals',
								'pretty_slug' => 'Animals',
							],
						],
						'title'            => 'Animals Images & Pictures',
						'subtitle'         => 'Download free animals images',
						'description'      => 'Passionate photographers have captured the most gorgeous animals in the world in their natural habitats and shared them with Unsplash. Now you can use these photos however you wish, for free!',
						'meta_title'       => 'Best 20+ Animals Pictures [HD] | Download Free Images on Unsplash',
						'meta_description' => 'Choose from hundreds of free animals pictures. Download HD animals photos for free on Unsplash.',
						'cover_photo'      => [
							'id'                       => 'adK3Vu70DEQ',
							'created_at'               => '2015-02-27T19:18:03-05:00',
							'updated_at'               => '2020-02-28T00:01:52-05:00',
							'promoted_at'              => '2015-02-27T19:18:03-05:00',
							'width'                    => 4239,
							'height'                   => 2808,
							'color'                    => '#ACA5A3',
							'description'              => null,
							'alt_description'          => 'selective focus photography of brown hamster',
							'urls'                     => [
								'raw'     => 'https://images.unsplash.com/photo-1425082661705-1834bfd09dca?ixlib=rb-1.2.1',
								'full'    => 'https://images.unsplash.com/photo-1425082661705-1834bfd09dca?ixlib=rb-1.2.1&q=85&fm=jpg&crop=entropy&cs=srgb',
								'regular' => 'https://images.unsplash.com/photo-1425082661705-1834bfd09dca?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=1080&fit=max',
								'small'   => 'https://images.unsplash.com/photo-1425082661705-1834bfd09dca?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=400&fit=max',
								'thumb'   => 'https://images.unsplash.com/photo-1425082661705-1834bfd09dca?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=200&fit=max',
							],
							'links'                    => [
								'self'              => 'https://api.unsplash.com/photos/adK3Vu70DEQ',
								'html'              => 'https://unsplash.com/photos/adK3Vu70DEQ?utm_source=WordPress-XWP&utm_medium=referral&utm_campaign=api-credit',
								'download'          => 'https://unsplash.com/photos/adK3Vu70DEQ/download?utm_source=WordPress-XWP&utm_medium=referral&utm_campaign=api-credit',
								'download_location' => 'https://api.unsplash.com/photos/adK3Vu70DEQ/download',
							],
							'categories'               => [],
							'likes'                    => 1325,
							'liked_by_user'            => false,
							'current_user_collections' => [],
							'user'                     => [
								'id'                 => '0e7bDgtjCJw',
								'updated_at'         => '2020-03-03T09:58:45-05:00',
								'username'           => 'sweetmangostudios',
								'name'               => 'Ricky  Kharawala',
								'first_name'         => 'Ricky ',
								'last_name'          => 'Kharawala',
								'twitter_username'   => 'rickykharawala',
								'portfolio_url'      => 'http://www.sweetmangostudios.com',
								'bio'                => null,
								'location'           => null,
								'links'              => [
									'self'      => 'https://api.unsplash.com/users/sweetmangostudios',
									'html'      => 'https://unsplash.com/@sweetmangostudios?utm_source=WordPress-XWP&utm_medium=referral&utm_campaign=api-credit',
									'photos'    => 'https://api.unsplash.com/users/sweetmangostudios/photos',
									'likes'     => 'https://api.unsplash.com/users/sweetmangostudios/likes',
									'portfolio' => 'https://api.unsplash.com/users/sweetmangostudios/portfolio',
									'following' => 'https://api.unsplash.com/users/sweetmangostudios/following',
									'followers' => 'https://api.unsplash.com/users/sweetmangostudios/followers',
								],
								'profile_image'      => [
									'small'  => 'https://images.unsplash.com/profile-1473176711009-5afa1898d622?ixlib=rb-1.2.1&q=80&fm=jpg&crop=faces&cs=tinysrgb&fit=crop&h=32&w=32',
									'medium' => 'https://images.unsplash.com/profile-1473176711009-5afa1898d622?ixlib=rb-1.2.1&q=80&fm=jpg&crop=faces&cs=tinysrgb&fit=crop&h=64&w=64',
									'large'  => 'https://images.unsplash.com/profile-1473176711009-5afa1898d622?ixlib=rb-1.2.1&q=80&fm=jpg&crop=faces&cs=tinysrgb&fit=crop&h=128&w=128',
								],
								'instagram_username' => 'kuriouskoala',
								'total_collections'  => 0,
								'total_likes'        => 9,
								'total_photos'       => 58,
								'accepted_tos'       => true,
							],
						],
					],
				],
				[
					'type'   => 'landing_page',
					'title'  => 'wolf',
					'source' => [
						'ancestry' => [
							'type'             => [
								'slug'        => 'images',
								'pretty_slug' => 'Images',
							],
							'category'         => [
								'slug'        => 'animals',
								'pretty_slug' => 'Animals',
							],
							'subcategory'      => [
								'slug'        => 'wolf',
								'pretty_slug' => 'Wolf',
							],
							'title'            => 'Wolf Images & Pictures',
							'subtitle'         => 'Download free wolf images',
							'description'      => "The wolf is legendary for a reason, and Unsplash photographers have managed to capture the power and majesty of this creature in it's natural habitat. You can access and use over 100 wolf images as you see fit, free of charge!",
							'meta_title'       => 'Best 100+ Wolf Pictures [HD] | Download Free Images on Unsplash',
							'meta_description' => 'Choose from hundreds of free wolf pictures. Download HD wolf photos for free on Unsplash.',
							'cover_photo'      => [
								'id'                       => '_zVGPn7IxNI',
								'created_at'               => '2019-02-14T12:41:41-05:00',
								'updated_at'               => '2020-02-28T00:01:57-05:00',
								'promoted_at'              => null,
								'width'                    => 3495,
								'height'                   => 5243,
								'color'                    => '#EBE7E2',
								'description'              => "She\u2019s a great model",
								'alt_description'          => 'shallow focus photo of black and white dog',
								'urls'                     => [
									'raw'     => 'https://images.unsplash.com/photo-1550165703-3f6ae6887b9b?ixlib=rb-1.2.1',
									'full'    => 'https://images.unsplash.com/photo-1550165703-3f6ae6887b9b?ixlib=rb-1.2.1&q=85&fm=jpg&crop=entropy&cs=srgb',
									'regular' => 'https://images.unsplash.com/photo-1550165703-3f6ae6887b9b?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=1080&fit=max',
									'small'   => 'https://images.unsplash.com/photo-1550165703-3f6ae6887b9b?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=400&fit=max',
									'thumb'   => 'https://images.unsplash.com/photo-1550165703-3f6ae6887b9b?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=200&fit=max',
								],
								'links'                    => [
									'self'              => 'https://api.unsplash.com/photos/_zVGPn7IxNI',
									'html'              => 'https://unsplash.com/photos/_zVGPn7IxNI?utm_source=WordPress-XWP&utm_medium=referral&utm_campaign=api-credit',
									'download'          => 'https://unsplash.com/photos/_zVGPn7IxNI/download?utm_source=WordPress-XWP&utm_medium=referral&utm_campaign=api-credit',
									'download_location' => 'https://api.unsplash.com/photos/_zVGPn7IxNI/download',
								],
								'categories'               => [],
								'likes'                    => 102,
								'liked_by_user'            => false,
								'current_user_collections' => [],
								'user'                     => [
									'id'                 => 'WjCjB5Jn3HU',
									'updated_at'         => '2020-02-22T00:55:44-05:00',
									'username'           => 'tahoe',
									'name'               => 'Tahoe',
									'first_name'         => 'Tahoe',
									'last_name'          => null,
									'twitter_username'   => 'tahoooe',
									'portfolio_url'      => 'https://tahoe.be',
									'bio'                => "\ud83d\udc15",
									'location'           => 'France',
									'links'              => [
										'self'      => 'https://api.unsplash.com/users/tahoe',
										'html'      => 'https://unsplash.com/@tahoe?utm_source=WordPress-XWP&utm_medium=referral&utm_campaign=api-credit',
										'photos'    => 'https://api.unsplash.com/users/tahoe/photos',
										'likes'     => 'https://api.unsplash.com/users/tahoe/likes',
										'portfolio' => 'https://api.unsplash.com/users/tahoe/portfolio',
										'following' => 'https://api.unsplash.com/users/tahoe/following',
										'followers' => 'https://api.unsplash.com/users/tahoe/followers',
									],
									'profile_image'      => [
										'small'  => 'https://images.unsplash.com/profile-1568132201228-4fbf2d071604image?ixlib=rb-1.2.1&q=80&fm=jpg&crop=faces&cs=tinysrgb&fit=crop&h=32&w=32',
										'medium' => 'https://images.unsplash.com/profile-1568132201228-4fbf2d071604image?ixlib=rb-1.2.1&q=80&fm=jpg&crop=faces&cs=tinysrgb&fit=crop&h=64&w=64',
										'large'  => 'https://images.unsplash.com/profile-1568132201228-4fbf2d071604image?ixlib=rb-1.2.1&q=80&fm=jpg&crop=faces&cs=tinysrgb&fit=crop&h=128&w=128',
									],
									'instagram_username' => null,
									'total_collections'  => 3,
									'total_likes'        => 82,
									'total_photos'       => 29,
									'accepted_tos'       => true,
								],
							],
						],
					],
					[
						'type'  => 'search',
						'title' => 'mammal',
					],
				],
			],
		];
	}

	/**
	 * Data provider for format_exception.
	 *
	 * @return array
	 */
	public function data_test_format_exception() {
		return [
			[ 'test_500', 500, 'Server error. An error occurred contacting the Unsplash API.' ],
			[ 'test_401', 401, 'Request unauthorized. Please check your Unsplash settings.' ],
			[ 'test_403', 403, 'Request forbidden. Please check your Unsplash settings.' ],
			[ 'test_418', 418, 'I\'m a teapot' ],
			[ 'test_0', 0, 'Server error. An error occurred contacting the Unsplash API.' ],
			[ 'test_foo', 'foo', 'Server error. An error occurred contacting the Unsplash API.' ],
		];
	}

	/**
	 * Test format_exception().
	 *
	 * @dataProvider data_test_format_exception
	 * @covers       \Unsplash\Rest_Controller::format_exception()
	 *
	 * @param string|int $code Error code.
	 * @param int        $error_status HTTP error state code.
	 * @param string     $message Message.
	 */
	public function test_format_exception( $code, $error_status, $message ) {
		$rest_controller = new Rest_Controller( new Plugin() );
		$wp_error        = $rest_controller->format_exception( $code, $error_status );
		$this->assertEquals( $wp_error->get_error_code(), $code );
		$this->assertEquals( wp_strip_all_tags( $wp_error->get_error_message() ), $message );
	}

	/**
	 * Test check_api_credentials().
	 *
	 * @covers       \Unsplash\Rest_Controller::check_api_credentials()
	 */
	public function test_check_api_credentials() {
		$rest_controller = new Rest_Controller( new Plugin() );
		$result          = $rest_controller->check_api_credentials();
		$this->assertTrue( $result );
	}

	/**
	 * Test check_api_credentials().
	 *
	 * @covers       \Unsplash\Rest_Controller::check_api_credentials()
	 */
	public function test_no_check_api_credentials() {
		add_filter( 'unsplash_api_credentials', [ $this, 'disable_unsplash_api_credentials' ] );
		$rest_controller = new Rest_Controller( new Plugin() );
		$wp_error        = $rest_controller->check_api_credentials();
		$this->assertEquals( $wp_error->get_error_code(), 'missing_api_credential' );
		$this->assertEquals( wp_strip_all_tags( $wp_error->get_error_message() ), 'The following API credential is missing: applicationId. Please go to Unsplash settings to setup this plugin.' );
		remove_filter( 'unsplash_api_credentials', [ $this, 'disable_unsplash_api_credentials' ] );
	}

	/**
	 * Test rest_ensure_response().
	 *
	 * @covers       \Unsplash\Rest_Controller::rest_ensure_response()
	 */
	public function test_rest_ensure_response() {
		$rest_controller = new Rest_Controller( new Plugin() );
		$request         = new WP_REST_Request();
		$response        = $rest_controller->rest_ensure_response( [ 'foo' => 'bar' ], $request );
		$data            = $response->get_data();
		$this->assertEqualSets( $data, [ 'foo' => 'bar' ] );
	}

	/**
	 * Test rest_ensure_response().
	 *
	 * @covers       \Unsplash\Rest_Controller::rest_ensure_response()
	 */
	public function test_rest_ensure_response_wp_error() {
		$rest_controller = new Rest_Controller( new Plugin() );
		$request         = new WP_REST_Request();
		$wp_error        = $rest_controller->rest_ensure_response( new \WP_Error( 'test_error', 'Testing' ), $request );
		$this->assertEquals( $wp_error->get_error_code(), 'test_error' );
		$this->assertEquals( $wp_error->get_error_message(), 'Testing' );
	}

	/**
	 * Test rest_ensure_response().
	 *
	 * @covers       \Unsplash\Rest_Controller::rest_ensure_response()
	 * @covers       \Unsplash\Rest_Controller::is_ajax_request()
	 */
	public function test_rest_ensure_response_ajax() {
		$rest_controller = new Rest_Controller( new Plugin() );
		$request         = new WP_REST_Request();
		$request->set_header( 'X-Requested-With', 'XMLHttpRequest' );
		$response = $rest_controller->rest_ensure_response( [ 'foo' => 'bar' ], $request );
		$data     = $response->get_data();
		$this->assertEqualSets( $data, [ 'foo' => 'bar' ] );
	}

	/**
	 * Test rest_ensure_response().
	 *
	 * @covers       \Unsplash\Rest_Controller::rest_ensure_response()
	 * @covers       \Unsplash\Rest_Controller::is_ajax_request()
	 */
	public function test_rest_ensure_response_wp_error_ajax() {
		$rest_controller = new Rest_Controller( new Plugin() );
		$request         = new WP_REST_Request();
		$request->set_header( 'X-Requested-With', 'XMLHttpRequest' );
		$response = $rest_controller->rest_ensure_response( new \WP_Error( 'test_error', 'Testing' ), $request );
		$data     = $response->get_data();
		$this->assertEqualSets(
			$data,
			[
				'success' => false,
				'data'    => [
					[
						'code'    => 'test_error',
						'message' => 'Testing',
						'data'    => [],
					],
				],
			]
		);
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
