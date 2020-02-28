<?php
/**
 * Tests for Types class.
 *
 * @package XWP\Unsplash
 */

namespace XWP\Unsplash;

use Mockery;
use WP_Mock;

/**
 * Tests for the Router class.
 */
class TestTypes extends TestCase {
	/**
	 * Test register_meta.
	 *
	 * @covers \XWP\Unsplash\Types::register_meta()
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
	 * @covers \XWP\Unsplash\Types::register_taxonomy()
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
