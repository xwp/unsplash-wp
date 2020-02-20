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
		$asset_file = $this->plugin->asset_dir( 'js/dist/browser.asset.php' );
		$asset      = require $asset_file;
		$version    = $asset['version'];

		$dependencies   = $asset['dependencies'];
		$dependencies[] = 'media-views';

		wp_enqueue_script(
			'unsplash_browser',
			$this->plugin->asset_url( 'js/dist/browser.js' ),
			$dependencies,
			$version
		);

		wp_localize_script(
			'unsplash_browser',
			'unsplash',
			[
				'tabTitle' => __( 'Unsplash', 'unsplash' ),
				'route'    => '/wp-json' . RestController::get_route(),
				'toolbar'  => [
					'heading' => __( 'Sort images', 'unsplash' ),
					'filters' => [
						'orderBy' => [
							'label' => __( 'Sort by type', 'unsplash' ),
							'types' => Photo::order_types(),
						],
						'search'  => [
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
