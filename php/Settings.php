<?php
/**
 * Settings class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

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
	 * Setup the plugin instance.
	 *
	 * @param Plugin $plugin Instance of the plugin abstraction.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Setup the hooks, actions and filters.
	 *
	 * @uses add_action() To add actions.
	 */
	public function init() {

		// Add the menu.
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );

		// Register the settings.
		add_action( 'admin_init', [ $this, 'add_settings' ] );
	}

	/**
	 * Adds the Unsplash admin menu.
	 */
	public function add_admin_menu() {
		add_options_page( 'Unsplash', 'Unsplash', 'manage_options', 'unsplash', [ $this, 'settings_page_render' ] );
	}

	/**
	 * Add the Unsplash settings.
	 */
	public function add_settings() {
		register_setting( 'unsplash', 'unsplash_settings' );

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
	}

	/**
	 * Renders the entire settings page.
	 */
	public function settings_page_render() {
		?>
		<form action='options.php' method='post' style="max-width: 800px">
			<h1>Unsplash</h1>
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
		$options = get_option( 'unsplash_settings' );
		?>
		<input type='text' class="widefat" name='unsplash_settings[access_key]' value='<?php echo esc_attr( isset( $options['access_key'] ) ? $options['access_key'] : '' ); ?>'>
		<?php
	}

	/**
	 * Renders the Secret Key.
	 */
	public function secret_key_render() {
		$options = get_option( 'unsplash_settings' );
		?>
		<input type='text' class="widefat" name='unsplash_settings[secret_key]' value='<?php echo esc_attr( isset( $options['secret_key'] ) ? $options['secret_key'] : '' ); ?>'>
		<?php
	}
}
