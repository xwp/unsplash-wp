<?php
/**
 * Bootstraps the Unsplash plugin.
 *
 * @package Unsplash
 */

namespace Unsplash;

use WP_Screen;

/**
 * Main plugin bootstrap file.
 */
class Plugin extends Plugin_Base {

	/**
	 * Hotlink class.
	 *
	 * @var Hotlink
	 */
	public $hotlink;

	/**
	 * Settings class.
	 *
	 * @var Settings
	 */
	public $settings;

	/**
	 * REST_Controller class.
	 *
	 * @var REST_Controller
	 */
	public $rest_controller;

	/**
	 * API instance.
	 *
	 * @var API
	 */
	public $api;

	/**
	 * API instance.
	 *
	 * @var Block_Type
	 */
	public $block_type;

	/**
	 * Initiate the plugin resources.
	 *
	 * @action plugins_loaded
	 */
	public function init() {
		$this->hotlink = new Hotlink( $this );
		$this->hotlink->init();

		$this->settings = new Settings( $this );
		$this->settings->init();

		$this->rest_controller = new REST_Controller( $this );
		$this->rest_controller->init();

		$this->api = new API( $this );

		$this->block_type = new Block_Type( $this );
		$this->block_type->init();

		// Manually add this filter as the plugin file name is dynamic.
		add_filter( 'plugin_action_links_' . $this->file, [ $this, 'action_links' ] );
	}

	/**
	 * Polyfill dependencies needed to enqueue our assets on WordPress 4.9 and below.
	 *
	 * @codeCoverageIgnore
	 *
	 * @action wp_default_scripts
	 *
	 * @param /WP_Scripts $wp_scripts Scripts.
	 */
	public function register_polyfill_scripts( $wp_scripts ) {

		// Only load assets if we're NOT on WP 5.0+.
		if ( version_compare( '5.0', get_bloginfo( 'version' ), '>' ) ) {
			$handles = [
				'wp-i18n',
				'wp-polyfill',
				'wp-url',
			];

			foreach ( $handles as $handle ) {
				if ( ! isset( $wp_scripts->registered[ $handle ] ) ) {
					$asset_file   = $this->dir_path . '/assets/js/' . $handle . '.asset.php';
					$asset        = require $asset_file;
					$dependencies = $asset['dependencies'];
					$version      = $asset['version'];

					$wp_scripts->add(
						$handle,
						$this->asset_url( sprintf( 'assets/js/%s.js', $handle ) ),
						$dependencies,
						$version
					);
				}
			}

			$vendor_scripts = [
				'lodash' => [
					'dependencies' => [],
					'version'      => '4.17.15',
				],
			];

			foreach ( $vendor_scripts as $handle => $handle_data ) {
				if ( ! isset( $wp_scripts->registered[ $handle ] ) ) {
					$path = $this->asset_url( sprintf( 'assets/js/vendor/%s.js', $handle ) );

					$wp_scripts->add( $handle, $path, $handle_data['dependencies'], $handle_data['version'], 1 );
				}
			}
		}
	}

	/**
	 * Load our media selector assets.
	 *
	 * @action wp_enqueue_media
	 */
	public function enqueue_media_scripts() {
		if ( ! current_user_can( 'upload_files' ) ) {
			return false;
		}
		$screen = ( function_exists( 'get_current_screen' ) ) ? get_current_screen() : false;

		if ( ! $screen instanceof WP_Screen ) {
			return false;
		}

		if ( 'post' !== $screen->base ) {
			return false;
		}

		// Enqueue media selector JS.
		$asset_file = $this->dir_path . '/assets/js/media-selector.asset.php';
		$asset      = is_readable( $asset_file ) ? require $asset_file : [];
		$version    = isset( $asset['version'] ) ? $asset['version'] : $this->asset_version();

		$dependencies   = isset( $asset['dependencies'] ) ? $asset['dependencies'] : [];
		$dependencies[] = 'media-views';
		$dependencies[] = 'wp-api-request';

		wp_enqueue_script(
			'unsplash-media-selector',
			$this->asset_url( 'assets/js/media-selector.js' ),
			$dependencies,
			$version,
			true
		);

		wp_localize_script(
			'unsplash-media-selector',
			'unsplash',
			[
				'tabTitle'  => esc_html__( 'Unsplash', 'unsplash' ),
				'route'     => rest_url( 'unsplash/v1/photos' ),
				'toolbar'   => [
					'filters' => [
						'search' => [
							'label'       => esc_html__( 'Search', 'unsplash' ),
							'placeholder' => esc_html__( 'Search free high-resolution photos', 'unsplash' ),
						],
					],
				],
				'noResults' => [
					'noMedia' => esc_html__( 'No items found.', 'unsplash' ),
				],
				'errors'    => [
					'generic' => esc_html__( 'The file was unable to be imported into the Media Library. Please try again', 'unsplash' ),
				],

			]
		);

		/*
		 * If the block editor is available, the featured image selector in the editor will need to be overridden. This
		 * is an extension of the media selector enqueued above and is separated from it because the required dependencies
		 * are not available in WP < 5.0. It would not make sense to polyfill these dependencies anyways since the block
		 * editor is not officially compatible with WP < 5.0.
		 */
		if ( has_action( 'enqueue_block_assets' ) ) {
			$asset_file = $this->dir_path . '/assets/js/featured-image-selector.asset.php';
			$asset      = is_readable( $asset_file ) ? require $asset_file : [];
			$version    = isset( $asset['version'] ) ? $asset['version'] : $this->asset_version();

			$dependencies   = isset( $asset['dependencies'] ) ? $asset['dependencies'] : [];
			$dependencies[] = 'unsplash-media-selector';

			wp_enqueue_script(
				'unsplash-featured-image-selector',
				$this->asset_url( 'assets/js/featured-image-selector.js' ),
				$dependencies,
				$version,
				true
			);
		}

		// Enqueue media selector CSS.
		wp_enqueue_style(
			'unsplash-media-selector-style',
			$this->asset_url( 'assets/css/media-selector-compiled.css' ),
			[],
			$this->asset_version()
		);

		wp_styles()->add_data( 'unsplash-media-selector-style', 'rtl', 'replace' );

		return true;
	}

	/**
	 * Load our admin assets.
	 *
	 * @action admin_enqueue_scripts
	 */
	public function enqueue_admin_scripts() {
		wp_enqueue_style(
			'unsplash-admin-style',
			$this->asset_url( 'assets/css/admin-compiled.css' ),
			[],
			$this->asset_version()
		);

		wp_styles()->add_data( 'unsplash-admin-style', 'rtl', 'replace' );
	}

	/**
	 * Custom wp_prepare_attachment_for_js copied from core.
	 *
	 * @param array $photo Photo object.
	 *
	 * @return array
	 */
	public function wp_prepare_attachment_for_js( array $photo ) {
		$credentials = $this->settings->get_credentials();
		$utm_source  = $credentials['utmSource'];
		$image       = new Image( $photo, $utm_source );

		$response = [
			'id'             => isset( $photo['id'] ) ? $photo['id'] : null,
			'unsplash_order' => isset( $photo['unsplash_order'] ) ? $photo['unsplash_order'] : null,
			'title'          => '',
			'filename'       => $image->get_field( 'file' ),
			'url'            => $image->get_field( 'original_url' ),
			'link'           => $image->get_field( 'links' )['html'],
			'alt'            => $image->get_field( 'alt' ),
			'author'         => $image->get_field( 'user' )['name'],
			'description'    => $image->get_field( 'description' ),
			'caption'        => $image->get_caption(),
			'color'          => $image->get_field( 'color' ),
			'name'           => $image->get_field( 'original_id' ),
			'height'         => $image->get_field( 'height' ),
			'width'          => $image->get_field( 'width' ),
			'status'         => 'inherit',
			'uploadedTo'     => 0,
			'date'           => strtotime( $image->get_field( 'created_at' ) ) * 1000,
			'modified'       => strtotime( $image->get_field( 'updated_at' ) ) * 1000,
			'menuOrder'      => 0,
			'mime'           => $image->get_field( 'mime_type' ),
			'type'           => 'image',
			'subtype'        => $image->get_field( 'ext' ),
			'icon'           => ! empty( $image->get_image_url( 'thumb' ) ) ? $this->get_original_url_with_size( $image->get_image_url( 'thumb' ), 150, 150 ) : null,
			'dateFormatted'  => mysql2date( 'F j, Y', $image->get_field( 'created_at' ) ),
			'nonces'         => [
				'update' => false,
				'delete' => false,
				'edit'   => false,
			],
			'editLink'       => false,
			'meta'           => false,
		];

		$response['sizes'] = $this->add_image_sizes( $image->get_field( 'original_url' ), $image->get_field( 'width' ), $image->get_field( 'height' ) );

		return $response;
	}

	/**
	 * Generate image sizes for Admin ajax / REST api.
	 *
	 * @param String $url Image URL.
	 * @param Int    $width Width of Image.
	 * @param Int    $height Height of Image.
	 *
	 * @return array
	 */
	public function add_image_sizes( $url, $width, $height ) {
		$width_medium  = 400;
		$height_medium = $this->get_image_height( $width, $height, $width_medium );
		$url_medium    = $this->get_original_url_with_size( $url, $width_medium, $height_medium );
		$sizes         = [
			'full'   => [
				'url'         => $url,
				'height'      => $height,
				'width'       => $width,
				'orientation' => 0,
			],
			'medium' => [
				'url'         => $url_medium,
				'height'      => $height_medium,
				'width'       => $width_medium,
				'orientation' => 0,
			],
		];

		foreach ( $this->image_sizes() as $name => $size ) {
			if ( array_key_exists( $name, $sizes ) ) {
				continue;
			}
			$_height = $this->get_image_height( $width, $height, absint( $size['width'] ), absint( $size['height'] ) );
			$_url    = $this->get_original_url_with_size( $url, $size['width'], $_height );

			$sizes[ $name ] = [
				'url'         => $_url,
				'height'      => (int) $_height,
				'width'       => (int) $size['width'],
				'orientation' => 0,
			];
		}

		return $sizes;
	}

	/**
	 * Calculate new image height, while preserving the aspect ratio.
	 *
	 * @param  int $width      Full width.
	 * @param  int $height     Full Height.
	 * @param  int $new_width  New Width.
	 * @param  int $new_height New height. Defaults to 0.
	 * @return int            Height of image.
	 */
	public function get_image_height( $width, $height, $new_width, $new_height = 0 ) {
		$_height = (int) ( ( $height / ( $width / $new_width ) ) );
		if ( $new_height ) {
			$_height = min( $_height, $new_height );
		}

		return $_height;
	}

	/**
	 * Helper function to get sized URL.
	 *
	 * @param string $url Original URL of unsplash asset.
	 * @param int    $width Width of image.
	 * @param int    $height Height of image.
	 * @param array  $attr Other attributes to be passed to the URL.
	 *
	 * @return string Format image url.
	 */
	public function get_original_url_with_size( $url, $width, $height, $attr = [] ) {
		$attr = array_merge(
			[
				'fm'  => 'jpg',
				'q'   => '85',
				'fit' => 'crop',
			],
			$attr,
			[
				'w' => absint( $width ),
				'h' => absint( $height ),
			]
		);
		$url  = add_query_arg(
			$attr,
			$url
		);

		return $url;
	}

	/**
	 * Get a list of image sizes.
	 *
	 * @return array
	 */
	public function image_sizes() {
		$sizes       = [];
		$image_sizes = get_intermediate_image_sizes(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_intermediate_image_sizes_get_intermediate_image_sizes
		if ( 0 === count( $image_sizes ) ) {
			$image_sizes = [ 'thumbnail', 'medium', 'medium_large', 'large' ];
		}

		$additional_sizes = wp_get_additional_image_sizes();
		foreach ( $image_sizes as $size_name ) {
			$size_data = [
				'width'  => 0,
				'height' => 0,
				'crop'   => false,
			];

			if ( isset( $additional_sizes[ $size_name ]['width'] ) ) {
				// For sizes added by plugins and themes.
				$size_data['width'] = intval( $additional_sizes[ $size_name ]['width'] );
			} else {
				// For default sizes set in options.
				$size_data['width'] = intval( get_option( "{$size_name}_size_w" ) );
			}

			if ( isset( $additional_sizes[ $size_name ]['height'] ) ) {
				$size_data['height'] = intval( $additional_sizes[ $size_name ]['height'] );
			} else {
				$size_data['height'] = intval( get_option( "{$size_name}_size_h" ) );
			}

			if ( empty( $size_data['width'] ) && empty( $size_data['height'] ) ) {
				// This size isn't set.
				continue;
			}

			if ( isset( $additional_sizes[ $size_name ]['crop'] ) ) {
				$size_data['crop'] = $additional_sizes[ $size_name ]['crop'];
			} else {
				$size_data['crop'] = get_option( "{$size_name}_crop" );
			}

			if ( ! is_array( $size_data['crop'] ) || empty( $size_data['crop'] ) ) {
				$size_data['crop'] = (bool) $size_data['crop'];
			}

			$sizes[ $size_name ] = $size_data;
		}

		return $sizes;
	}

	/**
	 * Register meta field for attachments.
	 *
	 * @action init
	 */
	public function register_meta() {
		$default_args = [
			'single'         => true,
			'show_in_rest'   => true,
			'object_subtype' => 'attachment',
		];

		$default_object_schema = [
			'type'                 => 'object',
			'properties'           => [],
			'additionalProperties' => true,
		];

		$meta_args = [
			'original_id'       => [],
			'original_link'     => [],
			'original_url'      => [
				'type'         => 'string',
				'show_in_rest' => [
					'name'   => 'original_url',
					'type'   => 'string',
					'schema' => [
						'type'   => 'string',
						'format' => 'uri',
					],
				],
			],
			'color'             => [],
			'unsplash_location' => [
				'type'         => 'object',
				'show_in_rest' => [
					'name'   => 'unsplash_location',
					'type'   => 'object',
					'schema' => $default_object_schema,
				],
			],
			'unsplash_sponsor'  => [
				'type'         => 'object',
				'show_in_rest' => [
					'name'   => 'unsplash_sponsor',
					'type'   => 'object',
					'schema' => $default_object_schema,
				],
			],
			'unsplash_exif'     => [
				'type'         => 'object',
				'show_in_rest' => [
					'name'   => 'unsplash_exif',
					'type'   => 'object',
					'schema' => $default_object_schema,
				],
			],
		];

		foreach ( $meta_args as $name => $args ) {
			$args = wp_parse_args( $args, $default_args );
			register_meta( 'post', $name, $args );
		}
	}

	/**
	 * Register taxonomies for attachments.
	 *
	 * @action init
	 */
	public function register_taxonomy() {
		$default_args = [
			'public'       => false,
			'rewrite'      => false,
			'hierarchical' => false,
			'show_in_rest' => true,
		];

		$tax_args = [
			'media_tag'     => [],
			'media_source'  => [
				'labels'            => [
					'name'          => esc_html__( 'Sources', 'unsplash' ),
					'singular_name' => esc_html__( 'Source', 'unsplash' ),
					'all_items'     => esc_html__( 'All Sources', 'unsplash' ),
				],
				'show_admin_column' => true,
			],
			'unsplash_user' => [
				'labels' => [
					'name'          => esc_html__( 'Users', 'unsplash' ),
					'singular_name' => esc_html__( 'User', 'unsplash' ),
					'all_items'     => esc_html__( 'All users', 'unsplash' ),
				],
			],
		];

		foreach ( $tax_args as $name => $args ) {
			$args = wp_parse_args( $args, $default_args );
			register_taxonomy( $name, 'attachment', $args );
		}
	}

	/**
	 * Add an admin notice on if credentials not setup.
	 *
	 * @action admin_notices
	 */
	public function admin_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$screen = ( function_exists( 'get_current_screen' ) ) ? get_current_screen() : false;

		if ( ! $screen instanceof WP_Screen ) {
			return false;
		}

		if ( 'settings_page_unsplash' === $screen->id ) {
			return false;
		}

		$credentials = $this->settings->get_credentials();
		if ( ! empty( $credentials['applicationId'] ) && $this->api->check_api_credentials() ) {
			$status = $this->api->check_api_status( $credentials, true, true );
			if ( ! is_wp_error( $status ) ) {
				return false;
			}

			$message = $status->get_error_message();
			if ( $message ) {
				printf( '<div class="notice notice-error is-dismissible"><p>%1$s</p></div>', wp_kses_post( $message ) );
			}

			return;
		}

		$class   = 'notice notice-warning is-dismissible notice-unsplash-global';
		$logo    = $this->asset_url( 'assets/images/logo.svg' );
		$title   = esc_html__( 'Unsplash', 'unsplash' );
		$message = $this->api->get_missing_credentials_message();


		printf(
			'<div class="%1$s"><h3><img src="%2$s" height="18" "/>   %3$s</h3><p>%4$s</p></div>',
			esc_attr( $class ),
			esc_url( $logo ),
			esc_html( $title ),
			wp_kses(
				$message,
				[
					'a'  => [
						'href' => [],
					],
					'br' => [],
				]
			)
		);
	}

	/**
	 * Add action link to plugin settings page.
	 *
	 * @param  array $links Plugin action links.
	 *
	 * @return array
	 */
	public function action_links( $links ) {
		$url     = get_admin_url( null, 'options-general.php?page=unsplash' );
		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $url ),
			esc_html__( 'Settings', 'unsplash' )
		);

		return $links;
	}
}
