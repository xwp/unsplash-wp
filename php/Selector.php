<?php
/**
 * Selector class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

/**
 * Plugin Selector.
 */
class Selector {

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
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
	}

	/**
	 * Load our classic editor assets.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$asset_file = $this->plugin->asset_dir( 'js/dist/selector.asset.php' );
		$asset      = is_readable( $asset_file ) ? require $asset_file : [];
		$version    = isset( $asset['version'] ) ? $asset['version'] : $this->plugin->version();

		$dependencies   = isset( $asset['dependencies'] ) ? $asset['dependencies'] : [];
		$dependencies[] = 'media-views';
		$dependencies[] = 'wp-api-request';

		wp_enqueue_script(
			'unsplash_selector',
			$this->plugin->asset_url( 'js/dist/selector.js' ),
			$dependencies,
			$version,
			true
		);

		wp_localize_script(
			'unsplash_selector',
			'unsplash',
			[
				'tabTitle' => __( 'Unsplash', 'unsplash' ),
				'route'    => rest_url( 'unsplash/v1/photos' ),
				'toolbar'  => [
					'filters' => [
						'search' => [
							'label' => __( 'Search', 'unsplash' ),
						],
					],
				],
			]
		);
	}

	/**
	 * Load our block assets.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		// Placeholder for gutenberg script.
	}
}
