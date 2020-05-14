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
	 * Test test_get().

	 * @covers       \Unsplash\API::get()
	 * @covers       \Unsplash\API::send_request()
	 */
	public function test_get() {
		$plugin = new Plugin();
		$plugin->init();
		$api     = new API( $plugin );
		$result1 = $api->get( 'uYpOYyJdhRE' );
		$this->assertFalse( $result1->get_cached() );
		$result2 = $api->get( 'uYpOYyJdhRE' );
		$this->assertTrue( $result2->get_cached() );
	}

	/**
	 * Test test_get().
	 *
	 * @covers       \Unsplash\API::get()
	 * @covers       \Unsplash\API::send_request()
	 */
	public function test_no_get() {
		$plugin = new Plugin();
		$plugin->init();
		$api      = new API( $plugin );
		$wp_error = $api->get( 'no-thank' );
		$this->assertEquals( $wp_error->get_error_code(), 'unsplash_api_error' );
		$this->assertEquals( wp_strip_all_tags( $wp_error->get_error_message() ), 'Unable to find Unsplash resource.' );
	}
	/**
	 * Test test_get().
	 *
	 * @covers       \Unsplash\API::get()
	 * @covers       \Unsplash\API::send_request()
	 */
	public function test_wrong_url_get_1() {
		$plugin = new Plugin();
		$plugin->init();
		$api = new API( $plugin );
		add_filter( 'unsplash_request_url', [ $this, 'invalid_unsplash_request_url' ] );
		$wp_error = $api->search( 'unused', [] );
		$this->assertEquals( $wp_error->get_error_code(), 'invalid_unsplash_response' );
		$this->assertEquals( wp_strip_all_tags( $wp_error->get_error_message() ), 'There appears to be a communication issue with Unsplash, please check status.unsplash.com and try again in a few minutes.' );
		remove_filter( 'unsplash_request_url', [ $this, 'invalid_unsplash_request_url' ] );
	}


	/**
	 * Test test_get().
	 *
	 * @covers       \Unsplash\API::get()
	 * @covers       \Unsplash\API::send_request()
	 */
	public function test_wrong_url_get_2() {
		$plugin = new Plugin();
		$plugin->init();
		$api = new API( $plugin );
		add_filter( 'unsplash_request_url', [ $this, 'fake_unsplash_request_url' ] );
		$wp_error = $api->search( 'unused', [] );
		$this->assertEquals( $wp_error->get_error_code(), 'http_request_failed' );
		$this->assertEquals( wp_strip_all_tags( $wp_error->get_error_message() ), 'There appears to be a communication issue with Unsplash, please check status.unsplash.com and try again in a few minutes.' );
		remove_filter( 'unsplash_request_url', [ $this, 'fake_unsplash_request_url' ] );
	}

	/**
	 * Test test_get().
	 *
	 * @covers       \Unsplash\API::get()
	 * @covers       \Unsplash\API::send_request()
	 */
	public function test_wrong_url_get_3() {
		$plugin = new Plugin();
		$plugin->init();
		$api = new API( $plugin );
		add_filter( 'http_response', [ $this, 'fake_http_response' ] );
		$wp_error = $api->search( 'unused', [] );
		$this->assertEquals( $wp_error->get_error_code(), 'unsplash_rate_limit' );
		$this->assertEquals( wp_strip_all_tags( $wp_error->get_error_message() ), 'The Unsplash API credentials supplied have been flagged for exceeding the permitted rate limit and have been temporarily disabled.' );
		remove_filter( 'http_response', [ $this, 'fake_http_response' ] );
	}

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
			[ 'test_404', 404, 'Unable to find Unsplash resource.' ],
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

	/**
	 * Test check_api_credentials().
	 *
	 * @covers \Unsplash\API::check_api_credentials()
	 */
	public function test_check_api_credentials() {
		$plugin = new Plugin();
		$plugin->init();
		$api    = new API( $plugin );
		$result = $api->check_api_credentials();
		$this->assertTrue( $result );
	}

	/**
	 * Test check_api_credentials().
	 *
	 * @covers \Unsplash\API::check_api_credentials()
	 */
	public function test_no_check_api_credentials() {
		add_filter( 'unsplash_api_credentials', [ $this, 'disable_unsplash_api_credentials' ] );
		$plugin = new Plugin();
		$plugin->init();
		$api      = new API( $plugin );
		$wp_error = $api->check_api_credentials();
		$this->assertEquals( $wp_error->get_error_code(), 'missing_api_credential' );
		$this->assertEquals( wp_strip_all_tags( $wp_error->get_error_message() ), 'The Unsplash plugin has not been provided the API access key. Please visit the Unsplash settings page and confirm that the API access key has been provided.' );
		remove_filter( 'unsplash_api_credentials', [ $this, 'disable_unsplash_api_credentials' ] );
	}

	/**
	 * Test check_api_credentials().
	 *
	 * @covers \Unsplash\API::check_api_credentials()
	 * @covers \Unsplash\API::get()
	 * @covers \Unsplash\API::send_request()
	 */
	public function test_no_check_api_credentials_again() {
		add_filter( 'unsplash_api_credentials', [ $this, 'disable_unsplash_api_credentials' ] );
		$plugin = new Plugin();
		$plugin->init();
		$api      = new API( $plugin );
		$wp_error = $api->get( 'uYpOYyJdhRE' );
		$this->assertEquals( $wp_error->get_error_code(), 'missing_api_credential' );
		$this->assertEquals( wp_strip_all_tags( $wp_error->get_error_message() ), 'The Unsplash plugin has not been provided the API access key. Please visit the Unsplash settings page and confirm that the API access key has been provided.' );
		remove_filter( 'unsplash_api_credentials', [ $this, 'disable_unsplash_api_credentials' ] );
	}

	/**
	 * Test check_api_status().
	 *
	 * @covers \Unsplash\API::check_api_status()
	 */
	public function test_check_api_status() {
		$plugin = new Plugin();
		$plugin->init();
		$api    = new API( $plugin );
		$result = $api->check_api_status( [], false );
		$this->assertTrue( $result );
	}

	/**
	 * Test check_api_status().
	 *
	 * @covers \Unsplash\API::check_api_status()
	 */
	public function test_check_api_status_missing_credentials() {
		$plugin = new Plugin();
		$plugin->init();
		$api    = new API( $plugin );
		$result = $api->check_api_status( [ 'applicationId' => '' ], false );
		$this->assertFalse( $result );
	}

	/**
	 * Test check_api_status().
	 *
	 * @covers \Unsplash\API::check_api_status()
	 */
	public function test_check_api_status_response_failed() {
		$plugin = new Plugin();
		$plugin->init();
		$api = new API( $plugin );
		add_filter( 'http_response', '__return_false' );
		$result = $api->check_api_status( [], false );
		remove_filter( 'http_response', '__return_false' );
		$this->assertFalse( $result );
	}

	/**
	 * Return a valid url but not the correct one.
	 *
	 * @return string
	 */
	public function invalid_unsplash_request_url() {
		return 'https://unsplash.com/';
	}

	/**
	 * Return fake url.
	 *
	 * @return string
	 */
	public function fake_unsplash_request_url() {
		return 'https://unsplash.fake/';
	}

	/**
	 * Fake rate limit exceeded.
	 *
	 * @param array $response Array from wp_remote_get.
	 *
	 * @return mixed
	 */
	public function fake_http_response( $response ) {
		$response['body'] = 'Rate Limit Exceeded';
		return $response;
	}

	/**
	 * Disable unsplash api details.
	 *
	 * @return array
	 */
	public function disable_unsplash_api_credentials() {
		return [
			'applicationId' => '',
			'utmSource'     => '',
		];
	}
}
