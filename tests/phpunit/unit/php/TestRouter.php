<?php
/**
 * Tests for Router class.
 *
 * @package Unsplash
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
		$plugin = new Router( Mockery::mock( Plugin::class ) );

		WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $plugin, 'enqueue_script' ], 10, 1 );

		$plugin->init();
	}

	/**
	 * Test enqueue_script.
	 *
	 * @covers \XWP\Unsplash\Router::enqueue_script()
	 */
	public function test_enqueue_script() {
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
				'1.2.3'
			);

		$block_extend = new Router( $plugin );
		$block_extend->enqueue_script();
	}
}
