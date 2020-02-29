<?php
/**
 * Tests for Router class.
 *
 * @package XWP\Unsplash
 */

namespace Unsplash;

use Mockery;
use WP_Mock;

/**
 * Tests for the Router class.
 */
class Test_Router extends Test_Case {
	/**
	 * Test init.
	 *
	 * @covers \Unsplash\Router::init()
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
	 * @covers \Unsplash\Router::enqueue_scripts()
	 */
	public function test_enqueue_scripts() {
		Mockery::mock( 'WP_REST_Controller' );
		$plugin = Mockery::mock( Plugin::class );

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
				'1.2.3',
				false
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

		$editor_mode = new Router( $plugin );
		$editor_mode->enqueue_scripts();
	}

	/**
	 * Test register_meta.
	 *
	 * @covers \Unsplash\Router::register_meta()
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
	 * @covers \Unsplash\Router::register_taxonomy()
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
