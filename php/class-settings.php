<?php
/**
 * Settings class.
 *
 * @package Unsplash
 */

namespace Unsplash;

/**
 * Plugin Settings.
 */
class Settings {

	/**
	 * Plugin interface.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * Key to use for encryption.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $key;

	/**
	 * Salt to use for encryption.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $salt;

	/**
	 * Array of encypted keys for secure data.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $encypted_keys = [ 'access_key', 'secret_key' ];

	/**
	 * Setting name.
	 */
	const OPTION_NAME = 'unsplash_settings';

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Instance of the plugin abstraction.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->key    = $this->get_default_key();
		$this->salt   = $this->get_default_salt();
	}

	/**
	 * Initiate the class.
	 */
	public function init() {
		$this->plugin->add_doc_hooks( $this );
	}

	/**
	 * Encrypts a value.
	 *
	 * If a user-based key is set, that key is used. Otherwise the default key is used.
	 *
	 * @param string $value Value to encrypt.
	 * @return string|bool Encrypted value, or false on failure.
	 */
	public function encrypt( $value ) {
		if ( ! extension_loaded( 'openssl' ) ) {
			return $value;
		}

		$method = 'aes-256-ctr';
		$ivlen  = openssl_cipher_iv_length( $method );
		$iv     = openssl_random_pseudo_bytes( $ivlen );

		$raw_value = openssl_encrypt( $value . $this->salt, $method, $this->key, 0, $iv );
		if ( ! $raw_value ) {
			return false;
		}

		return base64_encode( $iv . $raw_value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypts a value.
	 *
	 * If a user-based key is set, that key is used. Otherwise the default key is used.
	 *
	 * @param string $raw_value Value to decrypt.
	 * @return string|bool Decrypted value, or false on failure.
	 */
	public function decrypt( $raw_value ) {
		if ( ! extension_loaded( 'openssl' ) ) {
			return $raw_value;
		}

		$raw_value = base64_decode( $raw_value, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		$method = 'aes-256-ctr';
		$ivlen  = openssl_cipher_iv_length( $method );
		$iv     = substr( $raw_value, 0, $ivlen );

		$raw_value = substr( $raw_value, $ivlen );

		$value = openssl_decrypt( $raw_value, $method, $this->key, 0, $iv );
		if ( ! $value || substr( $value, - strlen( $this->salt ) ) !== $this->salt ) {
			return false;
		}

		return substr( $value, 0, - strlen( $this->salt ) );
	}

	/**
	 * Gets the default encryption key to use.
	 *
	 * @return string Default (not user-based) encryption key.
	 */
	private function get_default_key() {
		if ( defined( 'UNSPLASH_ENCRYPTION_KEY' ) && '' !== UNSPLASH_ENCRYPTION_KEY ) {
			return UNSPLASH_ENCRYPTION_KEY;
		}

		if ( defined( 'LOGGED_IN_KEY' ) && '' !== LOGGED_IN_KEY ) {
			return LOGGED_IN_KEY;
		}

		// If this is reached, you're either not on a live site or have a serious security issue.
		return 'there-is-no-secret-key';
	}

	/**
	 * Gets the default encryption salt to use.
	 *
	 * @return string Encryption salt.
	 */
	private function get_default_salt() {
		if ( defined( 'UNSPLASH_ENCRYPTION_SALT' ) && '' !== UNSPLASH_ENCRYPTION_SALT ) {
			return UNSPLASH_ENCRYPTION_SALT;
		}

		if ( defined( 'LOGGED_IN_SALT' ) && '' !== LOGGED_IN_SALT ) {
			return LOGGED_IN_SALT;
		}

		// If this is reached, you're either not on a live site or have a serious security issue.
		return 'there-is-no-secret-salt';
	}

	/**
	 * Adds the Unsplash admin menu.
	 *
	 * @action admin_menu
	 */
	public function add_admin_menu() {
		add_options_page( __( 'Unsplash', 'unsplash' ), __( 'Unsplash', 'unsplash' ), 'manage_options', 'unsplash', [ $this, 'settings_page_render' ] );
	}

	/**
	 * Add the Unsplash settings.
	 *
	 * @action admin_init
	 */
	public function add_settings() {
		$args = [
			'sanitize_callback' => [ $this, 'sanitize_settings' ],
		];
		register_setting( 'unsplash', $this::OPTION_NAME, $args );

		add_settings_section(
			'unsplash_section',
			__( 'Section Title', 'unsplash' ),
			[ $this, 'settings_section_render' ],
			'unsplash'
		);

		add_settings_field(
			'access_key',
			__( 'Access Key', 'unsplash' ),
			[ $this, 'access_key_render' ],
			'unsplash',
			'unsplash_section'
		);

		add_settings_field(
			'secret_key',
			__( 'Secret Key', 'unsplash' ),
			[ $this, 'secret_key_render' ],
			'unsplash',
			'unsplash_section'
		);

		add_settings_field(
			'utm_source',
			__( 'UTM Source', 'unsplash' ),
			[ $this, 'utm_source_render' ],
			'unsplash',
			'unsplash_section'
		);
	}

	/**
	 * Sanitize the Unsplash settings.
	 *
	 * @param array $settings Values being stored in the DB.
	 * @return array Sanitized and encrypted values.
	 */
	public function sanitize_settings( $settings ) {
		$options = get_option( $this::OPTION_NAME, [] );

		foreach ( $settings as $key => $value ) {
			$should_encrypt = (
				in_array( $key, $this->encypted_keys, true )
				&& ! empty( $settings[ $key ] )
				&& (
					! isset( $options[ $key ] )
					|| $options[ $key ] !== $settings[ $key ]
				)
			);

			if ( $should_encrypt ) {
				$settings[ $key ] = $this->encrypt( $value );
			} else {
				$settings[ $key ] = sanitize_text_field( $value );
			}
		}

		return $settings;
	}

	/**
	 * Renders the entire settings page.
	 */
	public function settings_page_render() {
		?>
		<form action='options.php' method='post' style="max-width: 800px">
			<h1><?php _e( 'Unsplash', 'unsplash' ); ?></h1>
			<?php
			settings_fields( 'unsplash' );
			do_settings_sections( 'unsplash' );
			submit_button();
			?>
		</form>
		<?php
	}

	/**
	 * Renders the settings section.
	 */
	public function settings_section_render() {
		echo esc_html__( 'Section Description', 'unsplash' );
	}

	/**
	 * Renders the Access Key.
	 */
	public function access_key_render() {
		$options = get_option( $this::OPTION_NAME, [] );
		?>
		<input type='password' class="widefat" name='unsplash_settings[access_key]' value='<?php echo esc_attr( isset( $options['access_key'] ) ? $options['access_key'] : '' ); ?>'>
		<?php
	}

	/**
	 * Renders the Secret Key.
	 */
	public function secret_key_render() {
		$options = get_option( $this::OPTION_NAME, [] );
		?>
		<input type='password' class="widefat" name='unsplash_settings[secret_key]' value='<?php echo esc_attr( isset( $options['secret_key'] ) ? $options['secret_key'] : '' ); ?>'>
		<?php
	}

	/**
	 * Renders the UTM Source Key.
	 */
	public function utm_source_render() {
		$options = get_option( $this::OPTION_NAME, [] );
		?>
		<input type='text' class="widefat" name='unsplash_settings[utm_source]' value='<?php echo esc_attr( isset( $options['utm_source'] ) ? $options['utm_source'] : '' ); ?>'>
		<?php
	}

	/**
	 * Helper function to get decrypted options.
	 *
	 * @param String $name Setting key name.
	 * @param String $env_name Env variable name.
	 *
	 * @return array|bool|false|string
	 */
	public function get_option( $name, $env_name ) {
		$options = get_option( $this::OPTION_NAME, [] );

		if ( ! isset( $options[ $name ] ) || empty( $options[ $name ] ) ) {
			return getenv( $env_name );
		}

		if ( in_array( $name, $this->encypted_keys, true ) ) {
			$value = $this->decrypt( $options[ $name ] );
		} else {
			$value = $options[ $name ];
		}

		return $value;
	}
}
