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
	 * Instance of REST Controller.
	 *
	 * @var Rest_Controller
	 */
	private static $rest_controller;

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

		self::$rest_controller = get_plugin_instance()->rest_controller;
		self::$rest_controller->init();
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

		$this->assertArrayHasKey( $this->get_route( '/search/(?P<search>[\w-]+)' ), static::$routes );
		$this->assertCount( 1, static::$routes[ $this->get_route( '/search/(?P<search>[\w-]+)' ) ] );
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
		$request  = new WP_REST_Request( 'GET', $this->get_route( '/search/motorcycle' ) );
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
		$request = new WP_REST_Request( 'GET', $this->get_route( '/search/motorcycle' ) );
		$request->set_query_params( [ 'collections' => $query_param ] );
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

		$this->assertEquals( $expected, static::$routes[ $this->get_route( '/search/(?P<search>[\w-]+)' ) ][0]['args'] );
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
		$actual = self::$rest_controller->is_ajax_request( $request );
		$this->assertEquals( $expected, $actual );
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
}