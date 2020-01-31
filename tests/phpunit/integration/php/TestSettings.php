<?php
/**
 * Tests for Settings class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

/**
 * Tests for the Settings class.
 *
 * @coversDefaultClass \XWP\Unsplash\Settings
 */
class TestSettings extends \WP_UnitTestCase {

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
		global $unsplash;
		$this->settings = $unsplash['settings'];
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
		$encrypted       = openssl_encrypt( 'test-value' . 'test-salt', self::METHOD, 'test-key', 0, $iv );
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
			'invalid settings' => [
				[
					'foo' => 'aaa',
					'bar' => 'bbb',
				],
				[],
			],
			'empty settings'   => [
				[
					'access_key' => '',
					'secret_key' => '',
				],
				[],
			],
			'valid settings'   => [
				[
					'access_key' => 'foo',
					'secret_key' => 'bar',
				],
				[ 'access_key', 'secret_key' ],
			],
		];
	}

	/**
	 * Test sanitize_settings.
	 *
	 * @covers ::sanitize_settings()
	 * @dataProvider data_test_sanitize_settings
	 *
	 * @param array $settings Settings.
	 * @param array $expected Expected array keys in sanitized settings.
	 */
	public function test_sanitize_settings( $settings, $expected ) {
		$sanitized_settings = $this->settings->sanitize_settings( $settings );
		// Only keys are compared since encrypting the same value will not give the same result.
		$this->assertEquals( $expected, array_keys( $sanitized_settings ) );
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
		$this->assertEquals( 'Section Description', $section );
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

		$expected = "\t\t<input type='password' class=\"widefat\" name='unsplash_settings[access_key]' value=''>\n\t\t";

		$this->assertEquals( $expected, $input );
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

		$expected = "\t\t<input type='password' class=\"widefat\" name='unsplash_settings[secret_key]' value=''>\n\t\t";

		$this->assertEquals( $expected, $input );
	}
}
