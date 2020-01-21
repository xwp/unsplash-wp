<?php
/**
 * Router class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

/**
 * Plugin Router.
 */
class Router {

	/**
	 * Plugin interface.
	 *
	 * @var Plugin
	 */
	protected $plugin;

	/**
	 * REST API controller.
	 *
	 * @var RestController
	 */
	protected $rest_controller;

	/**
	 * Setup the plugin instance.
	 *
	 * @param Plugin         $plugin Instance of the plugin abstraction.
	 * @param RestController $rest_controller Instance of the REST API controller.
	 */
	public function __construct( $plugin, $rest_controller ) {
		$this->plugin          = $plugin;
		$this->rest_controller = $rest_controller;
	}

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'rest_api_init', [ $this, 'rest_api_init' ] );
	}

	/**
	 * Load our block assets.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		wp_enqueue_script(
			'unsplash-js',
			$this->plugin->asset_url( 'js/dist/editor.js' ),
			[
				'lodash',
				'react',
				'wp-block-editor',
			],
			$this->plugin->asset_version()
		);
	}

	/**
	 * Initialize the REST API.
	 */
	public function rest_api_init() {
		$this->rest_controller->register_routes();
	}
}
