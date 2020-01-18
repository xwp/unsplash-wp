<?php
/**
 * Tests for Router class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

use Mockery;
use WP_Mock;
use XWP\Unsplash\RestAPI\RestController;

/**
 * Tests for the Router class.
 */
class TestRouter extends TestCase {

	/**
	 * This method is called before each test.
	 */
	public function setUp() {
		parent::setUp();

		// This is needed as it won't be available for unit tests.
		Mockery::mock( 'WP_REST_Controller' );
	}

	/**
	 * Test init.
	 *
	 * @covers \XWP\Unsplash\Router::init()
	 */
	public function test_init() {
		$plugin          = Mockery::mock( Plugin::class );
		$rest_controller = Mockery::mock( RestController::class );
		$router          = new Router( $plugin, $rest_controller );

		WP_Mock::expectActionAdded( 'enqueue_block_editor_assets', [ $router, 'enqueue_editor_assets' ], 10, 1 );
		WP_Mock::expectActionAdded( 'rest_api_init', [ $router, 'rest_api_init' ], 10, 1 );

		$router->init();
	}

	/**
	 * Test enqueue_editor_assets.
	 *
	 * @covers \XWP\Unsplash\Router::enqueue_editor_assets()
	 */
	public function test_enqueue_editor_assets() {
		$plugin          = Mockery::mock( Plugin::class );
		$rest_controller = Mockery::mock( RestController::class );

		$plugin->shouldReceive( 'asset_url' )
			->once()
			->with( 'js/dist/editor.js' )
			->andReturn( 'http://example.com/js/dist/editor.js' );

		$plugin->shouldReceive( 'asset_version' )
			->once()
			->andReturn( '1.2.3' );

		WP_Mock::userFunction( 'wp_enqueue_script' )
			->once()
			->with(
				'unsplash-js',
				'http://example.com/js/dist/editor.js',
				Mockery::type( 'array' ),
				'1.2.3'
			);

		$block_extend = new Router( $plugin, $rest_controller );
		$block_extend->enqueue_editor_assets();
	}

	/**
	 * Test rest_api_init.
	 *
	 * @covers \XWP\Unsplash\Router::rest_api_init()
	 */
	public function test_rest_api_init() {
		$plugin          = Mockery::mock( Plugin::class );
		$rest_controller = Mockery::mock( RestController::class );

		$rest_controller->shouldReceive( 'register_routes' )->once();

		$router = new Router( $plugin, $rest_controller );
		$router->rest_api_init();
	}
}
