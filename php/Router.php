<?php
/**
 * Router class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

use Crew\Unsplash\Photo;

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
	 * Post Type to add fields to.
	 */
	const POST_TYPE = 'attachment';

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
		add_action( 'wp_ajax_query-unsplash', [ $this, 'wp_ajax_query_unsplash' ] );
		remove_action( 'wp_ajax_send-attachment-to-editor', 'wp_ajax_send_attachment_to_editor', 1 );
		add_action( 'wp_ajax_send-attachment-to-editor', [ $this, 'wp_ajax_send_attachment_to_editor' ], 0 );
		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_action( 'init', [ $this, 'register_meta' ] );
	}

	/**
	 * Load our classic editor assets.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'unsplash-js',
			$this->plugin->asset_url( 'js/dist/editor.js' ),
			[
				'jquery',
				'media-views',
				'lodash',
			],
			$this->plugin->asset_version()
		);

		wp_localize_script(
			'unsplash-js',
			'unsplashSettings',
			[
				'tabTitle' => __( 'Unsplash', 'unsplash' ),
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

	/**
	 * Ajax handler for querying attachments.
	 */
	public function wp_ajax_query_unsplash() {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error();
		}

		$images = $this->get_images();

		$images = array_map( [ $this, 'wp_prepare_attachment_for_js' ], $images );
		$images = array_filter( $images );

		wp_send_json_success( $images );
	}

	/**
	 * Placeholder to get images
	 *
	 * @return mixed
	 */
	public function get_images() {
		$path = $this->plugin->asset_dir( 'php/response.json' );
		if ( is_readable( $path ) ) {
			$response = wp_safe_remote_get( esc_url_raw( $path ) );
			$images   = json_decode( wp_remote_retrieve_body( $response ), true );
		} else {
			$images = [];
		}
		return $images;
	}

	/**
	 * Custom wp_prepare_attachment_for_js copied from core.
	 *
	 * @param array $image Image object.
	 *
	 * @return array
	 */
	public function wp_prepare_attachment_for_js( array $image ) {
		$image = (object) $image;

		$response = array(
			'id'            => $image->id,
			'title'         => '',
			'filename'      => $image->id . '.jpg',
			'url'           => $image->urls['raw'],
			'link'          => $image->links['html'],
			'alt'           => $image->alt_description,
			'author'        => $image->author,
			'description'   => $image->description,
			'caption'       => '',
			'name'          => '',
			'height'        => $image->height,
			'width'         => $image->width,
			'status'        => 'inherit',
			'uploadedTo'    => 0,
			'date'          => strtotime( $image->created_at ) * 1000,
			'modified'      => strtotime( $image->updated_at ) * 1000,
			'menuOrder'     => 0,
			'mime'          => 'image/jpeg',
			'type'          => 'image',
			'subtype'       => 'jpeg',
			'icon'          => add_query_arg(
				[
					'w'   => 150,
					'h'   => 150,
					'q'   => 85,
					'fit' => 'crop',
				],
				$image->urls['raw']
			),
			'dateFormatted' => mysql2date( __( 'F j, Y' ), $image->created_at ),
			'nonces'        => array(
				'update' => false,
				'delete' => false,
				'edit'   => false,
			),
			'editLink'      => false,
			'meta'          => false,
		);

		$sizes = [
			'full' => [
				'url'    => $image->urls['raw'],
				'height' => $image->height,
				'width'  => $image->width,
			],
		];

		foreach ( $this->image_sizes() as $name => $size ) {
			$url            = add_query_arg(
				[
					'w'   => $size['height'],
					'h'   => $size['width'],
					'q'   => 85,
					'fit' => 'crop',
				],
				$image->urls['raw']
			);
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
	 * Get a list of image sizes.
	 *
	 * @return array
	 */
	public function image_sizes() {
		global $_wp_additional_image_sizes;

		$sizes = array();

		// @todo This is not supported by WordPress VIP and will require a new solution.
		foreach ( get_intermediate_image_sizes() as $s ) { // phpcs:ignore
			if ( in_array( $s, array( 'thumbnail', 'medium', 'medium_large', 'large' ), true ) ) {
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
	 * Ajax handler for sending an attachment to the editor.
	 *
	 * Generates the HTML to send an attachment to the editor.
	 * Backward compatible with the {@see 'media_send_to_editor'} filter
	 * and the chain of filters that follow.
	 */
	public function wp_ajax_send_attachment_to_editor() {
		check_ajax_referer( 'media-send-to-editor', 'nonce' );

		$attachment = ! empty( $_POST['attachment'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['attachment'] ) ) : [];
		if ( ! is_numeric( $attachment['id'] ) ) {
			$id            = $attachment['id'];
			$photo         = Photo::find( $id );
			$link          = $photo->download();
			$results       = $photo->toArray();
			$image         = new Image( $results );
			$importer      = new Import( $id, $image, $link );
			$attachment_id = $importer->process();
			if ( is_wp_error( $attachment_id ) ) {
				return wp_ajax_send_attachment_to_editor();
			}
			$_POST['attachment']['id'] = $attachment_id;
		}

		return wp_ajax_send_attachment_to_editor();
	}

	/**
	 * Register meta field for attachments.
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
					'name'          => __( 'Sources', 'unsplash' ),
					'singular_name' => __( 'Source', 'unsplash' ),
					'all_items'     => __( 'All Sources', 'unsplash' ),
				],
				'show_admin_column' => true,
			],
			'unsplash_user' => [
				'labels' => [
					'name'          => __( 'Users', 'unsplash' ),
					'singular_name' => __( 'User', 'unsplash' ),
					'all_items'     => __( 'All users', 'unsplash' ),
				],
			],
		];

		foreach ( $tax_args as $name => $args ) {
			$args = wp_parse_args( $args, $default_args );
			register_taxonomy( $name, self::POST_TYPE, $args );
		}
	}
}
