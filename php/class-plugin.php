<?php
/**
 * Bootstraps the Unsplash plugin.
 *
 * @package Unsplash
 */

namespace Unsplash;

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
	 * Post Type to add fields to.
	 */
	const POST_TYPE = 'attachment';

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
	}

	/**
	 * Load our media selector assets.
	 *
	 * @action wp_enqueue_media
	 */
	public function enqueue_media_scripts() {
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
				'tabTitle'  => __( 'Unsplash', 'unsplash' ),
				'route'     => rest_url( 'unsplash/v1/photos' ),
				'toolbar'   => [
					'filters' => [
						'search' => [
							'label' => __( 'Search the internet’s source of freely usable images.', 'unsplash' ),
						],
					],
				],
				'noResults' => [
					'noMedia' => __( 'No results found', 'unsplash' ),
					'image'   => $this->asset_url( 'assets/images/no-results.png' ),
				],

			]
		);

		wp_enqueue_style(
			'unsplash-media-selector-style',
			$this->asset_url( 'assets/css/media-selector-compiled.css' ),
			[],
			$this->asset_version()
		);

		wp_styles()->add_data( 'unsplash-media-selector-style', 'rtl', 'replace' );
	}


	/**
	 * Custom wp_prepare_attachment_for_js copied from core.
	 *
	 * @param array $photo Photo object.
	 *
	 * @return array
	 */
	public function wp_prepare_attachment_for_js( array $photo ) {
		$attrs = [
			'fm'  => 'jpg',
			'q'   => '85',
			'fit' => 'crop',
		];

		$response = [
			'id'            => isset( $photo['id'] ) ? $photo['id'] : null,
			'title'         => '',
			'filename'      => isset( $photo['unsplash_id'] ) ? $photo['unsplash_id'] . '.jpg' : null,
			'url'           => isset( $photo['urls']['raw'] ) ? $photo['urls']['raw'] : null,
			'link'          => isset( $photo['links']['html'] ) ? $photo['links']['html'] : null,
			'alt'           => isset( $photo['alt_description'] ) ? $photo['alt_description'] : null,
			'author'        => isset( $photo['author'] ) ? $photo['author'] : null,
			'description'   => isset( $photo['description'] ) ? $photo['description'] : null,
			'caption'       => '',
			'name'          => '',
			'height'        => isset( $photo['height'] ) ? $photo['height'] : null,
			'width'         => isset( $photo['width'] ) ? $photo['width'] : null,
			'status'        => 'inherit',
			'uploadedTo'    => 0,
			'date'          => isset( $photo['created_at'] ) ? strtotime( $photo['created_at'] ) * 1000 : null,
			'modified'      => isset( $photo['updated_at'] ) ? strtotime( $photo['updated_at'] ) * 1000 : null,
			'menuOrder'     => 0,
			'mime'          => 'image/jpeg',
			'type'          => 'image',
			'subtype'       => 'jpeg',
			'icon'          => isset( $photo['urls']['thumb'] ) ? $this->get_original_url_with_size( $photo['urls']['thumb'], 150, 150, $attrs ) : null,
			'dateFormatted' => isset( $photo['created_at'] ) ? mysql2date( __( 'F j, Y', 'unsplash' ), $photo['created_at'] ) : null,
			'nonces'        => [
				'update' => false,
				'delete' => false,
				'edit'   => false,
			],
			'editLink'      => false,
			'meta'          => false,
		];
		$width    = 400;
		$height   = (int) ceil( $photo['height'] / ( $photo['width'] / $width ) );
		$url      = isset( $photo['urls']['small'] ) ? $photo['urls']['small'] : $this->get_original_url_with_size( $photo['urls']['raw'], $width, $height, $attrs );
		$sizes    = [
			'full'   => [
				'url'    => $photo['urls']['raw'],
				'height' => $photo['height'],
				'width'  => $photo['width'],
			],
			'medium' => [
				'url'    => $url,
				'height' => $height,
				'width'  => $width,
			],
		];

		foreach ( $this->image_sizes() as $name => $size ) {
			if ( array_key_exists( $name, $sizes ) ) {
				continue;
			}
			$url            = $this->get_original_url_with_size( $photo['urls']['raw'], $size['width'], $size['height'], $attrs );
			$sizes[ $name ] = [
				'url'    => $url,
				'height' => $size['height'],
				'width'  => $size['width'],
			];
		}
		$response['sizes'] = $sizes;

		return $response;
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
		$attr = wp_parse_args(
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
		global $_wp_additional_image_sizes;

		$sizes = [];

		$image_sizes = get_intermediate_image_sizes(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_intermediate_image_sizes_get_intermediate_image_sizes
		if ( 0 === count( $image_sizes ) ) {
			return $sizes;
		}

		$default_sizes = [ 'thumbnail', 'medium', 'medium_large', 'large' ];
		foreach ( $image_sizes as $s ) {
			if ( in_array( $s, $default_sizes, true ) ) {
				$sizes[ $s ]['width']  = get_option( $s . '_size_w' );
				$sizes[ $s ]['height'] = get_option( $s . '_size_h' );
			} else {
				if ( isset( $_wp_additional_image_sizes, $_wp_additional_image_sizes[ $s ] ) ) {
					$sizes[ $s ]['height'] = $_wp_additional_image_sizes[ $s ]['height'];
				}
				$sizes[ $s ]['width'] = $_wp_additional_image_sizes[ $s ]['width'];
			}
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
			'object_subtype' => self::POST_TYPE,
		];

		$default_object_schema = [
			'type'                 => 'object',
			'properties'           => [],
			'additionalProperties' => true,
		];

		$meta_args = [
			'original_id'       => [],
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
			register_taxonomy( $name, self::POST_TYPE, $args );
		}
	}
}
