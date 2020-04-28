<?php
/**
 * Tests for API.
 *
 * @package Unsplash
 */

namespace Unsplash;

/**
 * Tests for the API class.
 *
 * @coversDefaultClass \Unsplash\API
 */
class Test_Api extends \WP_UnitTestCase {

	/**
	 * Data provider for format_exception.
	 *
	 * @return array
	 */
	public function data_test_format_exception() {
		return [
			[ 'test_500', 500, 'There appears to be a communication issue with Unsplash, please check status.unsplash.com and try again in a few minutes.' ],
			[ 'test_401', 401, 'The Unsplash API credentials supplied are not authorized. Please visit the Unsplash settings page to reconnect to Unsplash now.' ],
			[ 'test_403', 403, 'The Unsplash API credentials supplied are not authorized for this request. Please visit the Unsplash settings page to reconnect to Unsplash now.' ],
			[ 'test_400', 400, 'There appears to be a communication issue with Unsplash, please check status.unsplash.com and try again in a few minutes.' ],
			[ 'test_429', 429, 'The Unsplash API credentials supplied have been flagged for exceeding the permitted rate limit and have been temporarily disabled.' ],
			[ 'test_404', 404, 'Unable to find Unsplash photo.' ],
			[ 'test_418', 418, 'I\'m a teapot' ],
			[ 'test_0', 0, 'There appears to be a communication issue with Unsplash, please check status.unsplash.com and try again in a few minutes.' ],
			[ 'test_foo', 'foo', 'There appears to be a communication issue with Unsplash, please check status.unsplash.com and try again in a few minutes.' ],
		];
	}

	/**
	 * Test format_exception().
	 *
	 * @dataProvider data_test_format_exception
	 * @covers       \Unsplash\API::format_exception()
	 *
	 * @param string|int $code Error code.
	 * @param int        $error_status HTTP error state code.
	 * @param string     $message Message.
	 */
	public function test_format_exception( $code, $error_status, $message ) {
		$plugin = new Plugin();
		$plugin->init();
		$api      = new API( $plugin );
		$wp_error = $api->format_exception( $code, $error_status );
		$this->assertEquals( $wp_error->get_error_code(), $code );
		$this->assertEquals( wp_strip_all_tags( $wp_error->get_error_message() ), $message );
	}

}
