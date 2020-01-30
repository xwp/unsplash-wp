<?php
/**
 * Tests for Settings class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

use Mockery;
use WP_Mock;

/**
 * Tests for the Router class.
 *
 * @coversDefaultClass \XWP\Unsplash\Settings
 */
class TestSettings extends TestCase {

	/**
	 * Test constructor.
	 *
	 * @covers ::__construct()
	 */
	public function test_init() {
		$plugin   = Mockery::mock( Plugin::class );
		$settings = new Settings( $plugin );

		WP_Mock::expectActionAdded( 'admin_menu', [ $settings, 'add_admin_menu' ], 10, 1 );
		WP_Mock::expectActionAdded( 'admin_init', [ $settings, 'add_settings' ], 10, 1 );

		$settings->init();
	}
}
