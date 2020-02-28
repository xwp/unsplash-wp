<?php
/**
 * Tests for Selector class.
 *
 * @package XWP\Unsplash
 */

namespace XWP\Unsplash;

use Mockery;
use WP_Mock;

/**
 * Tests for the Selector class.
 */
class TestSelector extends TestCase {
	/**
	 * Test init.
	 *
	 * @covers \XWP\Unsplash\Selector::init()
	 */
	public function test_init() {
		Mockery::mock( 'WP_REST_Controller' );

		$plugin   = Mockery::mock( Plugin::class );
		$selector = new Selector( $plugin );

		WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $selector, 'enqueue_scripts' ], 10, 1 );

		$selector->init();
	}

	/**
	 * Test enqueue_scripts.
	 *
	 * @covers \XWP\Unsplash\Selector::enqueue_scripts()
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

		$editor_mode = new Selector( $plugin );
		$editor_mode->enqueue_scripts();
	}
}
