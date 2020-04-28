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
		add_options_page( 'Unsplash', 'Unsplash', 'manage_options', 'unsplash', [ $this, 'settings_page_render' ] );
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
		register_setting( 'unsplash', 'unsplash_settings', $args );

		add_settings_section(
			'unsplash_section',
			__( 'API Authenication', 'unsplash' ),
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
	}

	/**
	 * Sanitize the Unsplash settings.
	 *
	 * @param array $settings Values being stored in the DB.
	 * @return array Sanitized and encrypted values.
	 */
	public function sanitize_settings( $settings ) {
		$options = get_option( 'unsplash_settings' );

		foreach ( $settings as $key => $value ) {
			$should_encrypt = (
				in_array( $key, [ 'access_key', 'secret_key' ], true )
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
		$logo = $this->plugin->asset_url( 'assets/images/logo.png' );
		?>
		<form action='options.php' method='post' style="max-width: 800px">
			<h1><img src="<?php echo esc_url( $logo ); ?>" height="20" />  Unsplash</h1><br />
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
		echo esc_html__( 'These settings are required to use the Unpslash plugin.', 'unsplash' );
	}

	/**
	 * Renders the Access Key.
	 */
	public function access_key_render() {
		$options = get_option( 'unsplash_settings' );
		?>
		<input type='password' class="widefat" name='unsplash_settings[access_key]' aria-describedby="unsplash-key-description" value='<?php echo esc_attr( isset( $options['access_key'] ) ? $options['access_key'] : '' ); ?>'>
		<p class="description" id="unsplash-key-description"><?php esc_html_e( 'The API key is a public unique identifier required for authentication.', 'unsplash' ); ?></p>
		<?php
	}

	/**
	 * Renders the Secret Key.
	 */
	public function secret_key_render() {
		$options = get_option( 'unsplash_settings' );
		?>
		<input type='password' class="widefat" name='unsplash_settings[secret_key]' aria-describedby="unsplash-secret-description" value='<?php echo esc_attr( isset( $options['secret_key'] ) ? $options['secret_key'] : '' ); ?>'>
		<p class="description" id="unsplash-secret-description"><?php esc_html_e( 'The secret shared between Unsplash and your plugin, required for authentication.', 'unsplash' ); ?></p>
		<?php
	}

	/**
	 * Format the API credentials in an array and filter.
	 *
	 * @return mixed|void
	 */
	public function get_credentials() {
		$options        = get_option( 'unsplash_settings' );
		$site_name_slug = sanitize_title_with_dashes( get_bloginfo( 'name' ) );

		$credentials = [
			'applicationId' => ! empty( $options['access_key'] ) ? $this->decrypt( $options['access_key'] ) : getenv( 'UNSPLASH_ACCESS_KEY' ),
			'secret'        => ! empty( $options['secret_key'] ) ? $this->decrypt( $options['secret_key'] ) : getenv( 'UNSPLASH_SECRET_KEY' ),
			'utmSource'     => getenv( 'UNSPLASH_UTM_SOURCE' ) ? getenv( 'UNSPLASH_UTM_SOURCE' ) : $site_name_slug,
		];

		/**
		 * Filter API credentials.
		 *
		 * @param array $credentials Array of API credentials.
		 * @param array $options Unsplash settings.
		 */
		$credentials = apply_filters( 'unsplash_api_credentials', $credentials, $options );

		return $credentials;
	}
}
