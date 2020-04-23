<?php
/**
 * Tests for API cache class.
 *
 * @package Unsplash
 */

namespace Unsplash;

/**
 * Tests for the API cache class.
 *
 * @coversDefaultClass \Unsplash\Api_Cache
 */
class Test_Api_Cache extends \WP_UnitTestCase {

	/**
	 * Test get_cache.
	 *
	 * @covers ::get_cache()
	 */
	public function test_get_cache() {
		$request = new \WP_REST_Request( 'GET', '/unsplash/v1/photos' );
		$cache   = new Api_Cache( $request );
		$this->assertFalse( $cache->get_cache() );
	}

	/**
	 * Test set_cache.
	 *
	 * @covers ::set_cache()
	 */
	public function test_set_cache() {
		$request = new \WP_REST_Request( 'GET', '/unsplash/v1/photos' );
		$cache   = new Api_Cache( $request );
		$this->assertFalse( $cache->get_cache() );
		$this->assertEquals( $cache->get_is_cached(), 0 );
		$value = [ 'foo' => 'bar' ];
		$cache->set_cache( $value );
		$this->assertFalse( $cache->get_cache() );
		$this->assertEquals( $cache->get_is_cached(), 0 );
	}
}
