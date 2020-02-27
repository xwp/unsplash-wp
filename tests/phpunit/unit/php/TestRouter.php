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
	 * Test init.
	 *
	 * @covers \XWP\Unsplash\Router::init()
	 */
	public function test_init() {
		Mockery::mock( 'WP_REST_Controller' );

		$plugin = Mockery::mock( Plugin::class );
		$router = new Router( $plugin );

		WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $router, 'enqueue_scripts' ], 10, 1 );

		$router->init();
	}

	/**
	 * Test enqueue_scripts.
	 *
	 * @covers \XWP\Unsplash\Router::enqueue_scripts()
	 */
	public function test_enqueue_scripts() {
		Mockery::mock( 'WP_REST_Controller' );
		$plugin = Mockery::mock( Plugin::class );

		$plugin->shouldReceive( 'asset_dir' )
			->once()
			->andReturn( __DIR__ . '/assets/js/dist/selector.asset.php' );

		$plugin->shouldReceive( 'asset_url' )
			->once()
			->with( 'js/dist/selector.js' )
			->andReturn( 'http://example.com/js/dist/selector.js' );

		WP_Mock::userFunction( 'wp_enqueue_script' )
			->once()
			->with(
				'unsplash_selector',
				'http://example.com/js/dist/selector.js',
				[ 'wp-polyfill', 'media-views', 'wp-api-request' ],
				'44fc4d3ff739a64e2a7c5596a43c0b75',
				true
			);

		WP_Mock::userFunction( 'rest_url' )
			->once()
			->with( 'unsplash/v1/photos' )
			->andReturn( 'http://example.com/wp-json/unsplash/v1/photos' );

		WP_Mock::userFunction( 'wp_localize_script' )
			->once()
			->with(
				'unsplash_selector',
				'unsplash',
				[
					'tabTitle' => __( 'Unsplash', 'unsplash' ),
					'route'    => 'http://example.com/wp-json/unsplash/v1/photos',
					'toolbar'  => [
						'filters' => [
							'search' => [
								'label' => __( 'Search', 'unsplash' ),
							],
						],
					],
				]
			);

		$editor_mode = new Router( $plugin );
		$editor_mode->enqueue_scripts();
	}

	/**
	 * Test register_meta.
	 *
	 * @covers \XWP\Unsplash\Router::register_meta()
	 */
	public function test_register_meta() {
		Mockery::mock( 'WP_REST_Controller' );
		$plugin = Mockery::mock( Plugin::class );

		WP_Mock::userFunction( 'wp_parse_args' )->times( 6 );
		WP_Mock::userFunction( 'register_meta' )->times( 6 );

		$editor_mode = new Router( $plugin );
		$editor_mode->register_meta();
	}

	/**
	 * Test register_taxonomy.
	 *
	 * @covers \XWP\Unsplash\Router::register_taxonomy()
	 */
	public function test_register_taxonomy() {
		Mockery::mock( 'WP_REST_Controller' );
		$plugin = Mockery::mock( Plugin::class );

		WP_Mock::userFunction( 'wp_parse_args' )->times( 3 );
		WP_Mock::userFunction( 'register_taxonomy' )->times( 3 );

		$editor_mode = new Router( $plugin );
		$editor_mode->register_taxonomy();
	}
}
