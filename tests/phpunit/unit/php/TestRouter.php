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
		WP_Mock::userFunction( 'remove_action' )
			->once()
			->with(
				'wp_ajax_send-attachment-to-editor',
				'wp_ajax_send_attachment_to_editor',
				1
			);

		$plugin = new Router( Mockery::mock( Plugin::class ) );

		WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $plugin, 'enqueue_scripts' ], 10, 1 );

		$plugin->init();
	}

	/**
	 * Test enqueue_scripts.
	 *
	 * @covers \XWP\Unsplash\Router::enqueue_scripts()
	 */
	public function test_enqueue_scripts() {
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
}
