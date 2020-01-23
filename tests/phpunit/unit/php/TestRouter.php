<?php
/**
 * Tests for Router class.
 *
 * @package XWP\Unsplash
 */

namespace XWP\Unsplash;

use Mockery;
use WP_Mock;

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
		WP_Mock::userFunction( 'remove_action' )
			->once()
			->with(
				'wp_ajax_send-attachment-to-editor',
				'wp_ajax_send_attachment_to_editor',
				1
			);

		$plugin          = Mockery::mock( Plugin::class );
		$rest_controller = Mockery::mock( RestController::class );
		$router          = new Router( $plugin, $rest_controller );

		WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $plugin, 'enqueue_scripts' ], 10, 1 );
		WP_Mock::expectActionAdded( 'rest_api_init', [ $router, 'rest_api_init' ], 10, 1 );

		$router->init();
	}

	/**
	 * Test enqueue_scripts.
	 *
	 * @covers \XWP\Unsplash\Router::enqueue_scripts()
	 */
	public function test_enqueue_scripts() {
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

		WP_Mock::userFunction( 'wp_localize_script' )
			->once()
			->with(
				'unsplash-js',
				'unsplashSettings',
				[
					'tabTitle' => __( 'Unsplash', 'unsplash' ),
				]
			);

		$block_extend = new Router( $plugin, $rest_controller );
		$block_extend->enqueue_scripts();
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
