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
					'secret_key' => '',
				],
				[
					'access_key' => '',
					'secret_key' => '',
				],
			],
			'encrypted settings' => [
				[
					'access_key' => 'foo',
					'secret_key' => 'bar',
				],
				[
					'access_key' => 'foo',
					'secret_key' => 'bar',
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
			if ( in_array( $key, [ 'access_key', 'secret_key' ], true ) ) {
				$sanitized_settings[ $key ] = $this->settings->decrypt( $value );
			}
		}
		$this->assertEquals( $expected, $sanitized_settings );
	}

	/**
	 * Test sanitize_settings.
	 *
	 * @covers ::sanitize_settings()
	 *
	 * @param array $given Given array of raw values.
	 * @param array $expected Expected array of decrypted values.
	 */
	public function test_sanitize_settings_1() {
		$settings = [
			'access_key' => 'foo',
			'secret_key' => 'bar',
		];

		$encrypted_settings = $this->settings->sanitize_settings( $settings );

		add_filter(
			'pre_option_unsplash_settings',
			static function () use ( $encrypted_settings ) {
				return $encrypted_settings;
			},
			10,
			2
		);

		$new_settings = $this->settings->sanitize_settings(
			[
				'access_key' => 'foo',
				'secret_key' => '',
			]
		);

		// We have to test the decrypted vales because the encrypted values will never match.
		foreach ( $new_settings as $key => $value ) {
			if ( in_array( $key, [ 'access_key', 'secret_key' ], true ) && ! empty( $value ) ) {
				$new_settings[ $key ] = $this->settings->decrypt( $value );
			}
		}

		$this->assertEquals( $new_settings, $settings );
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
		$this->assertEquals( 'These settings are required to use the Unpslash plugin.', $section );
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
	 * Test secret_key_render.
	 *
	 * @covers ::secret_key_render()
	 */
	public function test_secret_key_render() {
		ob_start();
		$this->settings->secret_key_render();
		$input = ob_get_clean();

		$expected = "\t\t<input type='password' class=\"widefat\" name='unsplash_settings[secret_key]' aria-describedby=\"unsplash-secret-description\" value=''>\n\t\t";

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

		$this->assertEquals( [ 'applicationId', 'secret', 'utmSource' ], array_keys( $credentials ) );

		// Test UTM source.
		$expected_utm = sanitize_title_with_dashes( get_bloginfo( 'name' ) );
		$actual_utm   = $credentials['utmSource'];

		$this->assertEquals( $expected_utm, $actual_utm );
	}
}
