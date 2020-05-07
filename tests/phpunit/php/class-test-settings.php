<?php
/**
 * Tests for Settings class.
 *
 * @package Unsplash
 */

namespace Unsplash;

/**
 * Tests for the Settings class.
 *
 * @coversDefaultClass \Unsplash\Settings
 */
class Test_Settings extends \WP_UnitTestCase {

	const METHOD = 'aes-256-ctr';

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	public $settings;

	/**
	 * Setup.
	 *
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		$this->settings = new Settings( new Plugin() );
		$this->settings->init();
	}

	/**
	 * Forcibly set a property of an object that would otherwise not be possible.
	 *
	 * @param object|string $class     Class instance to set the property on, or class name containing the property.
	 * @param string        $property  Property name.
	 * @param mixed         $value New value to assign the property.
	 *
	 * @throws \ReflectionException When the class or property are invalid.
	 */
	protected function force_set_property( $class, $property, $value ) {
		$reflection_property = new \ReflectionProperty( $class, $property );
		$reflection_property->setAccessible( true );
		$reflection_property->setValue( $class, $value );
	}

	/**
	 * Test constructor.
	 *
	 * @covers ::__construct()
	 */
	public function test_construct() {
		$this->assertEquals( true, did_action( 'plugins_loaded', [ $this->settings, 'init' ] ) );
	}

	/**
	 * Test init.
	 *
	 * @covers ::init()
	 */
	public function test_init() {
		$this->assertEquals( 10, has_action( 'admin_menu', [ $this->settings, 'add_admin_menu' ] ) );
		$this->assertEquals( 10, has_action( 'admin_init', [ $this->settings, 'add_settings' ] ) );
	}

	/**
	 * Test encrypt.
	 *
	 * @covers ::encrypt()
	 */
	public function test_encrypt() {
		$this->force_set_property( $this->settings, 'key', 'test-key' );
		$this->force_set_property( $this->settings, 'salt', 'test-salt' );

		// The result is base64_encoded.
		$encrypted       = $this->settings->encrypt( 'test-value' );
		$base_64_decoded = base64_decode( $encrypted, true );
		$this->assertNotFalse( $base_64_decoded );

		// Decrypt.
		$iv_len    = openssl_cipher_iv_length( self::METHOD );
		$iv        = substr( $base_64_decoded, 0, $iv_len );
		$raw_value = substr( $base_64_decoded, $iv_len );
		$decrypted = openssl_decrypt( $raw_value, self::METHOD, 'test-key', 0, $iv );
		$value     = substr( $decrypted, 0, -strlen( 'test-salt' ) );

		$this->assertEquals( 'test-value', $value );
	}

	/**
	 * Test decrypt.
	 *
	 * @covers ::decrypt()
	 */
	public function test_decrypt() {
		$this->force_set_property( $this->settings, 'key', 'test-key' );
		$this->force_set_property( $this->settings, 'salt', 'test-salt' );

		// Encrypt 'test-value' and ensure that it is decrypted successfully.
		$iv_len          = openssl_cipher_iv_length( self::METHOD );
		$iv              = openssl_random_pseudo_bytes( $iv_len );
		$encrypted       = openssl_encrypt( 'test-valuetest-salt', self::METHOD, 'test-key', 0, $iv );
		$encrypted_value = base64_encode( $iv . $encrypted );
		$decrypted_value = $this->settings->decrypt( $encrypted_value );

		$this->assertEquals( 'test-value', $decrypted_value );
	}

	/**
	 * Test add_admin_menu.
	 *
	 * @covers ::add_admin_menu()
	 * @covers ::add_settings()
	 */
	public function test_add_admin_menu_and_add_settings() {
		$this->settings->add_admin_menu();
		$this->settings->add_settings();

		global $wp_settings_sections, $wp_settings_fields;

		$this->assertTrue( ! empty( $wp_settings_sections['unsplash']['unsplash_section'] ) );
		$this->assertTrue( ! empty( $wp_settings_fields['unsplash']['unsplash_section']['access_key'] ) );
	}

	/**
	 * Data for test_sanitize_settings.
	 *
	 * @return array
	 */
	public function data_test_sanitize_settings() {
		return [
			'ignored settings'   => [
				[
					'foo' => 'aaa',
					'bar' => '<script>bar</script>',
				],
				[
					'foo' => 'aaa',
					'bar' => '',
				],
			],
			'empty settings'     => [
				[
					'access_key' => '',
				],
				[
					'access_key' => '',
				],
			],
			'encrypted settings' => [
				[
					'access_key' => 'foo',
				],
				[
					'access_key' => 'foo',
				],
			],
		];
	}

	/**
	 * Test sanitize_settings.
	 *
	 * @covers ::sanitize_settings()
	 * @dataProvider data_test_sanitize_settings
	 *
	 * @param array $given Given array of raw values.
	 * @param array $expected Expected array of decrypted values.
	 */
	public function test_sanitize_settings( $given, $expected ) {
		$sanitized_settings = $this->settings->sanitize_settings( $given );

		// We have to test the decrypted vales because the encrypted values will never match.
		foreach ( $sanitized_settings as $key => $value ) {
			if ( 'access_key' === $key ) {
				$sanitized_settings[ $key ] = $this->settings->decrypt( $value );
			}
		}
		$this->assertEquals( $expected, $sanitized_settings );
	}

	/**
	 * Test that setting value is retained when an empty value is passed.
	 *
	 * @covers ::sanitize_settings()
	 */
	public function test_sanitize_settings_not_update_vale_when_empty() {
		$settings = [
			'access_key' => 'foo',
		];

		add_filter( 'pre_option_unsplash_settings', [ $this, 'get_mocked_settings' ], 10, 3 );

		$new_settings = $this->settings->sanitize_settings(
			[
				'access_key' => 'foo',
			]
		);

		// We have to test the decrypted vales because the encrypted values will never match.
		foreach ( $new_settings as $key => $value ) {
			if ( ! empty( $value ) && 'access_key' === $key ) {
				$new_settings[ $key ] = $this->settings->decrypt( $value );
			}
		}

		$this->assertEquals( $new_settings, $settings );

		remove_filter( 'pre_option_unsplash_settings', [ $this, 'get_mocked_settings' ], 10 );
	}

	/**
	 * Return a mocked version of settings.
	 *
	 * @return array Mocked settings.
	 */
	public function get_mocked_settings() {
		remove_filter( 'pre_option_unsplash_settings', [ $this, 'get_mocked_settings' ], 10 );

		$settings = $this->settings->sanitize_settings(
			[
				'access_key' => 'foo',
			]
		);

		add_filter( 'pre_option_unsplash_settings', [ $this, 'get_mocked_settings' ], 10, 3 );

		return $settings;
	}

	/**
	 * Test settings_page_render.
	 *
	 * @covers ::settings_page_render()
	 * @covers ::redirect_auth()
	 */
	public function test_settings_page_render() {
		$mock = $this->getMockBuilder( '\\Unsplash\Settings' )
			->setConstructorArgs( [ new Plugin() ] )
			->setMethods(
				[
					'redirect',
				]
			)
			->getMock();

		$mock->expects( $this->any() )
			->method( 'redirect' )
			->will( $this->returnValue( true ) );

		$mock->init();
		$mock->redirect_auth( 'foo-is-notice', 'updated' );

		ob_start();
		$mock->settings_page_render();
		$page = ob_get_clean();
		$this->assertContains( 'Authorize', $page );
		$this->assertContains( 'foo-is-notice', $page );
	}

	/**
	 * Test handle_auth_flow.
	 *
	 * @covers ::handle_auth_flow()
	 */
	public function test_handle_auth_flow() {
		$plugin = get_plugin_instance();

		$mock = $this->getMockBuilder( '\\Unsplash\Settings' )
			->setConstructorArgs( [ $plugin ] )
			->setMethods(
				[
					'redirect',
					'get_code',
					'get_client_id',
					'get_access_token',
				]
			)
			->getMock();

		$mock->expects( $this->once() )
			->method( 'redirect' )
			->will( $this->returnValue( true ) );

		$mock->expects( $this->once() )
			->method( 'get_code' )
			->will( $this->returnValue( true ) );

		$mock->expects( $this->once() )
			->method( 'get_client_id' )
			->will( $this->returnValue( getenv( 'UNSPLASH_ACCESS_KEY' ) ) );

		$mock->expects( $this->once() )
			->method( 'get_access_token' )
			->will( $this->returnValue( true ) );

		$mock->init();
		$mock->handle_auth_flow();

		$options = get_option( 'unsplash_auth' );
		$this->assertEquals( 'updated', $options['type'] );
	}

	/**
	 * Test handle_auth_flow.
	 *
	 * @covers ::handle_auth_flow()
	 */
	public function test_handle_auth_flow_error() {
		$plugin = get_plugin_instance();

		$mock = $this->getMockBuilder( '\\Unsplash\Settings' )
			->setConstructorArgs( [ $plugin ] )
			->setMethods(
				[
					'redirect',
					'get_code',
					'get_client_id',
					'get_access_token',
				]
			)
			->getMock();

		$mock->expects( $this->once() )
			->method( 'redirect' )
			->will( $this->returnValue( true ) );

		$mock->expects( $this->once() )
			->method( 'get_code' )
			->will( $this->returnValue( true ) );

		$mock->expects( $this->once() )
			->method( 'get_client_id' )
			->will( $this->returnValue( '12345' ) );

		$mock->expects( $this->once() )
			->method( 'get_access_token' )
			->will( $this->returnValue( true ) );

		$mock->init();
		$mock->handle_auth_flow();

		$options = get_option( 'unsplash_auth' );
		$this->assertEquals( 'error', $options['type'] );
	}

	/**
	 * Test get_code.
	 *
	 * @covers ::get_code()
	 */
	public function test_get_code() {
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'auth' );
		$_REQUEST['code']     = 12345;
		$this->assertEquals( 12345, $this->settings->get_code() );
	}

	/**
	 * Test get_code.
	 *
	 * @covers ::get_code()
	 */
	public function test_get_code_failed() {
		$_REQUEST['_wpnonce'] = 12345;
		$_REQUEST['code']     = 12345;
		$this->assertFalse( $this->settings->get_code() );
	}

	/**
	 * Test get_access_token.
	 *
	 * @covers ::get_access_token()
	 */
	public function test_get_access_token() {
		$filter = function() {
			return [
				'body' => wp_json_encode(
					[
						'access_token' => 54321,
					]
				),
			];
		};
		add_filter( 'http_response', $filter );
		$this->assertEquals( 54321, $this->settings->get_access_token( 12345 ) );
		remove_filter( 'http_response', $filter );
	}

	/**
	 * Test get_access_token.
	 *
	 * @covers ::get_access_token()
	 */
	public function test_get_access_token_error() {
		$mock = $this->getMockBuilder( '\\Unsplash\Settings' )
			->setConstructorArgs( [ get_plugin_instance() ] )
			->setMethods(
				[
					'redirect',
				]
			)
			->getMock();

		$mock->expects( $this->once() )
			->method( 'redirect' )
			->will( $this->returnValue( true ) );

		add_filter( 'http_response', '__return_false' );
		$this->assertFalse( $mock->get_access_token( 12345 ) );
		remove_filter( 'http_response', '__return_false' );
	}

	/**
	 * Test get_access_token.
	 *
	 * @covers ::get_access_token()
	 */
	public function test_get_access_token_error_fallthrough() {
		$mock = $this->getMockBuilder( '\\Unsplash\Settings' )
			->setConstructorArgs( [ get_plugin_instance() ] )
			->setMethods(
				[
					'redirect',
				]
			)
			->getMock();

		$mock->expects( $this->once() )
			->method( 'redirect' )
			->will( $this->returnValue( true ) );

		$filter = function() {
			return [
				'body' => '',
			];
		};

		add_filter( 'http_response', $filter );
		$this->assertFalse( $mock->get_access_token( 12345 ) );
		remove_filter( 'http_response', $filter );
	}

	/**
	 * Test get_client_id.
	 *
	 * @covers ::get_client_id()
	 */
	public function test_get_client_id() {
		$filter = function() {
			return [
				'body' => wp_json_encode(
					[
						'client_id' => 54321,
					]
				),
			];
		};
		add_filter( 'http_response', $filter );
		$this->assertEquals( 54321, $this->settings->get_client_id( 12345 ) );
		remove_filter( 'http_response', $filter );
	}

	/**
	 * Test get_client_id.
	 *
	 * @covers ::get_client_id()
	 */
	public function test_get_client_id_error() {
		$mock = $this->getMockBuilder( '\\Unsplash\Settings' )
			->setConstructorArgs( [ get_plugin_instance() ] )
			->setMethods(
				[
					'redirect',
				]
			)
			->getMock();

		$mock->expects( $this->once() )
			->method( 'redirect' )
			->will( $this->returnValue( true ) );

		add_filter( 'http_response', '__return_false' );
		$this->assertFalse( $mock->get_client_id( 12345 ) );
		remove_filter( 'http_response', '__return_false' );
	}

	/**
	 * Test get_client_id.
	 *
	 * @covers ::get_client_id()
	 */
	public function test_get_client_id_error_fallthrough() {
		$mock = $this->getMockBuilder( '\\Unsplash\Settings' )
			->setConstructorArgs( [ get_plugin_instance() ] )
			->setMethods(
				[
					'redirect',
				]
			)
			->getMock();

		$mock->expects( $this->once() )
			->method( 'redirect' )
			->will( $this->returnValue( true ) );

		$filter = function() {
			return [
				'body' => '',
			];
		};

		add_filter( 'http_response', $filter );
		$this->assertFalse( $mock->get_client_id( 12345 ) );
		remove_filter( 'http_response', $filter );
	}

	/**
	 * Test settings_section_render.
	 *
	 * @covers ::settings_section_render()
	 */
	public function test_settings_section_render() {
		ob_start();
		$this->settings->settings_section_render();
		$section = ob_get_clean();
		$this->assertContains( 'An API access key is required to use the Unsplash plugin.', $section );
	}

	/**
	 * Test access_key_render.
	 *
	 * @covers ::access_key_render()
	 */
	public function test_access_key_render() {
		ob_start();
		$this->settings->access_key_render();
		$input = ob_get_clean();

		$expected = "\t\t<input type='password' class=\"widefat\" name='unsplash_settings[access_key]' aria-describedby=\"unsplash-key-description\" value=''>\n\t\t";

		$this->assertContains( $expected, $input );
	}

	/**
	 *
	 * Test get_credentials.
	 *
	 * @covers ::get_credentials()
	 */
	public function test_get_credentials() {
		$credentials = $this->settings->get_credentials();

		$this->assertEquals( [ 'applicationId', 'utmSource' ], array_keys( $credentials ) );

		// Test UTM source.
		$expected_utm = sanitize_title_with_dashes( get_bloginfo( 'name' ) );
		$actual_utm   = $credentials['utmSource'];

		$this->assertEquals( $expected_utm, $actual_utm );
	}
}
