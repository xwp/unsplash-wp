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
	 * Post Type to add fields to.
	 */
	const POST_TYPE = 'attachment';

	/**
	 * Unsplash REST API Controller.
	 *
	 * @var UnsplashRestController
	 */
	protected $rest_controller;

	/**
	 * Setup the plugin instance.
	 *
	 * @param Plugin $plugin Instance of the plugin abstraction.
	 */
	public function __construct( $plugin ) {
		$this->plugin          = $plugin;
		$this->rest_controller = new UnsplashRestController();
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
		add_action( 'rest_api_init', [ $this->rest_controller, 'register_routes' ] );
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
		$html       = isset( $_POST['html'] ) ? wp_kses_post( wp_unslash( $_POST['html'] ) ) : '';
		$align      = isset( $attachment['align'] ) ? $attachment['align'] : 'none';
		if ( is_numeric( $attachment['id'] ) ) {
			$id = intval( $attachment['id'] );

			$post = get_post( $id );
			if ( ! $post ) {
				wp_send_json_error();
			}

			if ( 'attachment' !== $post->post_type ) {
				wp_send_json_error();
			}

			if ( current_user_can( 'edit_post', $id ) ) {
				// If this attachment is unattached, attach it. Primarily a back compat thing.
				$insert_into_post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

				if ( 0 === $post->post_parent && $insert_into_post_id ) {
					wp_update_post(
						array(
							'ID'          => $id,
							'post_parent' => $insert_into_post_id,
						)
					);
				}
			}

			$url = empty( $attachment['url'] ) ? '' : $attachment['url'];
			$rel = ( strpos( $url, 'attachment_id' ) || get_attachment_link( $id ) === $url );

			remove_filter( 'media_send_to_editor', 'image_media_send_to_editor' );

			if ( 'image' === substr( $post->post_mime_type, 0, 5 ) ) {

				$size = isset( $attachment['image-size'] ) ? $attachment['image-size'] : 'medium';
				$alt  = isset( $attachment['image_alt'] ) ? $attachment['image_alt'] : '';

				// No whitespace-only captions.
				$caption = isset( $attachment['post_excerpt'] ) ? $attachment['post_excerpt'] : '';
				if ( '' === trim( $caption ) ) {
					$caption = '';
				}

				$title = ''; // We no longer insert title tags into <img> tags, as they are redundant.
				$html  = get_image_send_to_editor( $id, $caption, $title, $align, $url, $rel, $size, $alt );
			} elseif ( wp_attachment_is( 'video', $post ) || wp_attachment_is( 'audio', $post ) ) {
				$html = isset( $_POST['html'] ) ? wp_kses_post( wp_unslash( $_POST['html'] ) ) : '';
			} else {
				$html = isset( $attachment['post_title'] ) ? $attachment['post_title'] : '';
				$rel  = $rel ? ' rel="attachment wp-att-' . $id . '"' : ''; // Hard-coded string, $id is already sanitized.

				if ( ! empty( $url ) ) {
					$html = '<a href="' . esc_url( $url ) . '"' . $rel . '>' . $html . '</a>';
				}
			}
		} else {
			$images = $this->get_images();
			$image  = array_filter(
				$images,
				static function ( $var ) use ( $attachment ) {
					return ( $var['id'] === $attachment['id'] );
				}
			);
			if ( $image ) {
				$data = array_shift( $image );
				$size = isset( $attachment['image-size'] ) ? $attachment['image-size'] : 'medium';
				$alt  = isset( $attachment['image_alt'] ) ? $attachment['image_alt'] : '';

				$class   = 'align' . esc_attr( $align ) . ' size-' . esc_attr( $size ) . ' wp-image-' . $data['id'];
				$img_src = $data['urls']['raw'];
				$html    = '<img src="' . esc_url( $img_src ) . '" alt="' . esc_attr( $alt ) . '" ' . $title . 'class="' . $class . '" />';

			}
		}
		/** This filter is documented in wp-admin/includes/media.php */
		$html = apply_filters( 'media_send_to_editor', $html, $id, $attachment );

		wp_send_json_success( $html );
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
