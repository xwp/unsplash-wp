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

		add_action( 'wp_default_scripts', [ $this, 'register_polyfill_scripts' ] );
		add_action( 'wp_enqueue_media', [ $this, 'enqueue_media_scripts' ] );
		add_action( 'enqueue_block_assets', [ $this, 'enqueue_block_assets' ], 100 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'init', [ $this, 'register_meta' ] );
		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_action( 'admin_notices', [ $this, 'admin_notice' ] );
		add_action( 'print_media_templates', [ $this, 'add_media_templates' ] );

		add_filter( 'wp_prepare_attachment_for_js', [ $this, 'add_unsplash_author_meta' ], 10, 2 );
	}

	/**
	 * Polyfill dependencies needed to enqueue our assets on WordPress 4.9 and below.
	 *
	 * @codeCoverageIgnore
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

		$post = get_post();
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
				'postId'    => ( $post ) ? $post->ID : null,
			]
		);

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
	 * Enqueue block editor assets.
	 */
	public function enqueue_block_assets() {
		/*
		 * If the block editor is available, the featured image selector in the editor will need to be overridden. This
		 * is an extension of the media selector enqueued above and is separated from it because the required dependencies
		 * are not available in WP < 5.0. It would not make sense to polyfill these dependencies anyways since the block
		 * editor is not officially compatible with WP < 5.0.
		 */
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

	/**
	 * Load our admin assets.
	 */
	public function enqueue_admin_scripts() {
		wp_enqueue_style(
			'unsplash-admin-style',
			$this->asset_url( 'assets/css/admin-compiled.css' ),
			[],
			$this->asset_version()
		);

		wp_styles()->add_data( 'unsplash-admin-style', 'rtl', 'replace' );

		$screen = ( function_exists( 'get_current_screen' ) ) ? get_current_screen() : false;

		if ( ! $screen instanceof WP_Screen || 'upload' !== $screen->base ) {
			return false;
		}

		// Enqueue media library JS.
		$asset_file = $this->dir_path . '/assets/js/media-library.asset.php';
		$asset      = is_readable( $asset_file ) ? require $asset_file : [];
		$version    = isset( $asset['version'] ) ? $asset['version'] : $this->asset_version();

		$dependencies   = isset( $asset['dependencies'] ) ? $asset['dependencies'] : [];
		$dependencies[] = 'media-views';

		wp_enqueue_script(
			'unsplash-media-library-js',
			$this->asset_url( 'assets/js/media-library.js' ),
			$dependencies,
			$version,
			true
		);
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

		$author_links = $image->get_field( 'user' )['links'];

		$response = [
			'id'                        => isset( $photo['id'] ) ? $photo['id'] : null,
			'unsplash_order'            => isset( $photo['unsplash_order'] ) ? $photo['unsplash_order'] : null,
			'title'                     => '',
			'filename'                  => $image->get_field( 'file' ),
			'url'                       => $image->get_field( 'original_url' ),
			'link'                      => $image->get_field( 'links' )['html'],
			'alt'                       => $image->get_field( 'alt' ),
			'author'                    => $image->get_field( 'user' )['name'],
			'unsplashAuthorLink'        => ! empty( $author_links ) && ! empty( $author_links['html'] ) ? $author_links['html'] : '',
			'description'               => $image->get_field( 'description' ),
			'caption'                   => $image->get_caption(),
			'color'                     => $image->get_field( 'color' ),
			'name'                      => $image->get_field( 'original_id' ),
			'height'                    => $image->get_field( 'height' ),
			'width'                     => $image->get_field( 'width' ),
			'status'                    => 'inherit',
			'uploadedTo'                => 0,
			'date'                      => strtotime( $image->get_field( 'created_at' ) ) * 1000,
			'modified'                  => strtotime( $image->get_field( 'updated_at' ) ) * 1000,
			'menuOrder'                 => 0,
			'mime'                      => $image->get_field( 'mime_type' ),
			'type'                      => 'image',
			'subtype'                   => $image->get_field( 'ext' ),
			'icon'                      => ! empty( $image->get_image_url( 'thumb' ) ) ? $this->get_original_url_with_size( $image->get_image_url( 'thumb' ), 150, 150 ) : null,
			'dateFormatted'             => mysql2date( 'F j, Y', $image->get_field( 'created_at' ) ),
			'nonces'                    => [
				'update' => false,
				'delete' => false,
				'edit'   => false,
			],
			'editLink'                  => false,
			'meta'                      => false,
			'originalUnsplashImageURL'  => $image->get_field( 'links' )['html'],
			'originalUnsplashImageName' => esc_html__( 'Unsplash', 'unsplash' ),
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
	 * Add unsplash author meta to admin ajax.
	 *
	 * @param array   $response Data for admin ajax.
	 * @param WP_Post $attachment Attachment object.
	 *
	 * @return mixed
	 */
	public function add_unsplash_author_meta( array $response, $attachment ) {
		if ( ! $attachment instanceof \WP_Post ) {
			return $response;
		}

		$author = get_the_terms( $attachment, 'unsplash_user' );
		if ( ! empty( $author ) && ! is_wp_error( $author ) ) {
			$author = reset( $author );
			$meta   = get_term_meta( $author->term_id, 'unsplash_meta', true );

			if ( ! empty( $meta ) ) {
				$response['unsplashAuthor']     = isset( $meta['name'] ) ? $meta['name'] : '';
				$response['unsplashAuthorLink'] = isset( $meta['links'], $meta['links']['html'] ) ? $meta['links']['html'] : '';
			}
		}

		$image_meta = (array) get_post_meta( $attachment->ID, 'unsplash_attachment_metadata', true );
		if ( ! empty( $image_meta ) && ! empty( $image_meta['image_meta'] ) && ! empty( $image_meta['image_meta']['created_timestamp'] ) ) {
			$created_at                    = date_create( $image_meta['image_meta']['created_timestamp'] );
			$response['unsplashCreatedAt'] = $created_at ? $created_at->format( 'F j, Y' ) : '';
		}

		return $response;
	}

	/**
	 * Add media templates.
	 */
	public function add_media_templates() { ?>
		<?php // phpcs:disable  WordPress.WP.I18n.MissingArgDomain

		$alt_text_description = sprintf(
		/* translators: 1: Link to tutorial, 2: Additional link attributes, 3: Accessibility text. */
			__( '<a href="%1$s" %2$s>Describe the purpose of the image%3$s</a>. Leave empty if the image is purely decorative.' ),
			esc_url( 'https://www.w3.org/WAI/tutorials/images/decision-tree' ),
			'target="_blank" rel="noopener noreferrer"',
			sprintf(
				'<span class="screen-reader-text"> %s</span>',
				/* translators: Accessibility text. */
				esc_html__( '(opens in a new tab)' )
			)
		);

		?>
		<script type="text/html" id="tmpl-unsplash-attachment-details-two-column">
			<div class="attachment-media-view {{ data.orientation }}">
				<h2 class="screen-reader-text"><?php esc_html_e( 'Attachment Preview' ); ?></h2>
				<div class="thumbnail thumbnail-{{ data.type }}">
					<# if ( data.uploading ) { #>
						<div class="media-progress-bar"><div></div></div>
					<# } else if ( data.sizes && data.sizes.large ) { #>
						<img class="details-image" src="{{ data.sizes.large.url }}" draggable="false" alt="" />
					<# } else if ( data.sizes && data.sizes.full ) { #>
						<img class="details-image" src="{{ data.sizes.full.url }}" draggable="false" alt="" />
					<# } else if ( -1 === jQuery.inArray( data.type, [ 'audio', 'video' ] ) ) { #>
						<img class="details-image icon" src="{{ data.icon }}" draggable="false" alt="" />
					<# } #>

					<# if ( 'audio' === data.type ) { #>
					<div class="wp-media-wrapper">
						<audio style="visibility: hidden" controls class="wp-audio-shortcode" width="100%" preload="none">
							<source type="{{ data.mime }}" src="{{ data.url }}"/>
						</audio>
					</div>
					<# } else if ( 'video' === data.type ) {
						var w_rule = '';
						if ( data.width ) {
							w_rule = 'width: ' + data.width + 'px;';
						} else if ( wp.media.view.settings.contentWidth ) {
							w_rule = 'width: ' + wp.media.view.settings.contentWidth + 'px;';
						}
					#>
					<div style="{{ w_rule }}" class="wp-media-wrapper wp-video">
						<video controls="controls" class="wp-video-shortcode" preload="metadata"
							<# if ( data.width ) { #>width="{{ data.width }}"<# } #>
							<# if ( data.height ) { #>height="{{ data.height }}"<# } #>
							<# if ( data.image && data.image.src !== data.icon ) { #>poster="{{ data.image.src }}"<# } #>>
							<source type="{{ data.mime }}" src="{{ data.url }}"/>
						</video>
					</div>
					<# } #>

					<div class="attachment-actions">
						<# if ( 'image' === data.type && ! data.uploading && data.sizes && data.can.save ) { #>
						<button type="button" class="button edit-attachment"><?php esc_html_e( 'Edit Image' ); ?></button>
						<# } else if ( 'pdf' === data.subtype && data.sizes ) { #>
						<p><?php esc_html_e( 'Document Preview' ); ?></p>
						<# } #>
					</div>
				</div>
			</div>
			<div class="attachment-info">
				<span class="settings-save-status" role="status">
					<span class="spinner"></span>
					<span class="saved"><?php esc_html_e( 'Saved.' ); ?></span>
				</span>
				<div class="details">
					<h2 class="screen-reader-text"><?php esc_html_e( 'Details' ); ?></h2>
					<# if ( data.unsplashAuthorLink ) { #>
						<div class="author unsplash-author-link"><strong><?php esc_html_e( 'Photo by', 'unsplash' ); ?>:</strong> <a href="{{ data.unsplashAuthorLink }}" target="_blank" rel="noopener noreferrer">{{ data.unsplashAuthor || data.author }}</a></div>
					<# } #>

					<div class="filename"><strong><?php esc_html_e( 'File name:' ); ?></strong> {{ data.filename }}</div>
					<# if ( ! data.unsplashAuthorLink ) { #>
					<div class="filename"><strong><?php esc_html_e( 'File type:' ); ?></strong> {{ data.mime }}</div>
					<# } #>
					<# if ( data.unsplashCreatedAt ) { #>
					<div class="uploaded unsplash-created-at"><strong><?php esc_html_e( 'Date:' ); ?></strong> {{ data.unsplashCreatedAt }}</div>
					<# } #>
					<div class="uploaded"><strong><?php esc_html_e( 'Uploaded on:' ); ?></strong> {{ data.dateFormatted }}</div>

					<div class="file-size"><strong><?php esc_html_e( 'File size:' ); ?></strong> {{ data.filesizeHumanReadable }}</div>
					<# if ( 'image' === data.type && ! data.uploading ) { #>
						<# if ( data.width && data.height ) { #>
							<div class="dimensions"><strong><?php esc_html_e( 'Dimensions:' ); ?></strong>
								<?php
								/* translators: 1: A number of pixels wide, 2: A number of pixels tall. */
								printf( esc_html__( '%1$s by %2$s pixels' ), '{{ data.width }}', '{{ data.height }}' );
								?>
							</div>
						<# } #>

						<# if ( data.originalUnsplashImageURL && data.originalUnsplashImageName ) { #>
							<strong><?php esc_html_e( 'Original image:' ); ?></strong>
							<a href="{{ data.originalUnsplashImageURL }}">{{data.originalUnsplashImageName}}</a>
						<# } else if ( data.originalImageURL && data.originalImageName ) { #>
							<strong><?php esc_html_e( 'Original image:' ); ?></strong>
							<a href="{{ data.originalImageURL }}">{{data.originalImageName}}</a>
						<# } #>
					<# } #>

					<# if ( data.fileLength && data.fileLengthHumanReadable ) { #>
						<div class="file-length"><strong><?php esc_html_e( 'Length:' ); ?></strong>
							<span aria-hidden="true">{{ data.fileLength }}</span>
							<span class="screen-reader-text">{{ data.fileLengthHumanReadable }}</span>
						</div>
					<# } #>

					<# if ( 'audio' === data.type && data.meta.bitrate ) { #>
						<div class="bitrate">
							<strong><?php esc_html_e( 'Bitrate:' ); ?></strong> {{ Math.round( data.meta.bitrate / 1000 ) }}kb/s
							<# if ( data.meta.bitrate_mode ) { #>
							{{ ' ' + data.meta.bitrate_mode.toUpperCase() }}
							<# } #>
						</div>
					<# } #>

					<div class="compat-meta">
						<# if ( data.compat && data.compat.meta ) { #>
							{{{ data.compat.meta }}} <?php // phpcs:ignore WordPressVIPMinimum.Security.Mustache.OutputNotation ?>
						<# } #>
					</div>
				</div>

				<div class="settings">
					<# var maybeReadOnly = data.can.save || data.allowLocalEdits ? '' : 'readonly'; #>
					<# if ( 'image' === data.type ) { #>
						<span class="setting has-description" data-setting="alt">
							<label for="attachment-details-two-column-alt-text" class="name"><?php esc_html_e( 'Alternative Text' ); ?></label>
							<input type="text" id="attachment-details-two-column-alt-text" value="{{ data.alt }}" aria-describedby="alt-text-description" {{ maybeReadOnly }} />
						</span>
						<p class="description" id="alt-text-description"><?php echo wp_kses_post( $alt_text_description ); ?></p>
					<# } #>
					<?php if ( post_type_supports( 'attachment', 'title' ) ) : ?>
					<span class="setting" data-setting="title">
						<label for="attachment-details-two-column-title" class="name"><?php esc_html_e( 'Title' ); ?></label>
						<input type="text" id="attachment-details-two-column-title" value="{{ data.title }}" {{ maybeReadOnly }} />
					</span>
					<?php endif; ?>
					<# if ( 'audio' === data.type ) { #>
					<?php
					foreach ( array(
						'artist' => esc_html__( 'Artist' ),
						'album'  => esc_html__( 'Album' ),
					) as $key => $label ) :
						?>
					<span class="setting" data-setting="<?php echo esc_attr( $key ); ?>">
						<label for="attachment-details-two-column-<?php echo esc_attr( $key ); ?>" class="name"><?php echo esc_html( $label ); ?></label>
						<input type="text" id="attachment-details-two-column-<?php echo esc_attr( $key ); ?>" value="{{ data.<?php echo esc_attr( $key ); ?> || data.meta.<?php echo esc_attr( $key ); ?> || '' }}" />
					</span>
					<?php endforeach; ?>
					<# } #>
					<span class="setting" data-setting="caption">
						<label for="attachment-details-two-column-caption" class="name"><?php esc_html_e( 'Caption' ); ?></label>
						<textarea id="attachment-details-two-column-caption" {{ maybeReadOnly }}>{{ data.caption }}</textarea>
					</span>
					<span class="setting" data-setting="description">
						<label for="attachment-details-two-column-description" class="name"><?php esc_html_e( 'Description' ); ?></label>
						<textarea id="attachment-details-two-column-description" {{ maybeReadOnly }}>{{ data.description }}</textarea>
					</span>
					<span class="setting">
						<span class="name"><?php esc_html_e( 'Uploaded By' ); ?></span>
						<span class="value">{{ data.authorName }}</span>
					</span>
					<# if ( data.uploadedToTitle ) { #>
						<span class="setting">
							<span class="name"><?php esc_html_e( 'Uploaded To' ); ?></span>
							<# if ( data.uploadedToLink ) { #>
								<span class="value"><a href="{{ data.uploadedToLink }}">{{ data.uploadedToTitle }}</a></span>
							<# } else { #>
								<span class="value">{{ data.uploadedToTitle }}</span>
							<# } #>
						</span>
					<# } #>
					<span class="setting" data-setting="url">
						<label for="attachment-details-two-column-copy-link" class="name"><?php esc_html_e( 'Copy Link' ); ?></label>
						<input type="text" id="attachment-details-two-column-copy-link" value="{{ data.url }}" readonly />
					</span>
					<div class="attachment-compat"></div>
				</div>

				<div class="actions">
					<a class="view-attachment" href="{{ data.link }}"><?php esc_html_e( 'View attachment page' ); ?></a>
					<# if ( data.can.save ) { #> |
						<a href="{{ data.editLink }}"><?php esc_html_e( 'Edit more details' ); ?></a>
					<# } #>
					<# if ( ! data.uploading && data.can.remove ) { #> |
						<?php if ( MEDIA_TRASH ) : ?>
							<# if ( 'trash' === data.status ) { #>
								<button type="button" class="button-link untrash-attachment"><?php esc_html_e( 'Restore from Trash' ); ?></button>
							<# } else { #>
								<button type="button" class="button-link trash-attachment"><?php esc_html_e( 'Move to Trash' ); ?></button>
							<# } #>
						<?php else : ?>
							<button type="button" class="button-link delete-attachment"><?php esc_html_e( 'Delete Permanently' ); ?></button>
						<?php endif; ?>
					<# } #>
				</div>
			</div>
		</script>

		<script type="text/html" id="tmpl-unsplash-attachment-details">
			<h2>
				<?php esc_html_e( 'Attachment Details' ); ?>
				<span class="settings-save-status" role="status">
					<span class="spinner"></span>
					<span class="saved"><?php esc_html_e( 'Saved.' ); ?></span>
				</span>
			</h2>
			<div class="attachment-info">
				<div class="thumbnail thumbnail-{{ data.type }}">
					<# if ( 'image' === data.type && data.sizes ) { #>
						<img src="{{ data.size.url }}" draggable="false" alt="" />
					<# } else { #>
						<img src="{{ data.icon }}" class="icon" draggable="false" alt="" />
					<# } #>
				</div>

				<div class="details">
					<# if ( data.unsplashAuthorLink ) { #>
						<div class="author"><strong><?php esc_html_e( 'Photo by', 'unsplash' ); ?>:</strong> <a href="{{ data.unsplashAuthorLink }}" target="_blank" rel="noopener noreferrer">{{ data.unsplashAuthor || data.author }}</a></div>
					<# } #>

					<div class="filename"><strong><?php esc_html_e( 'File name', 'unsplash' ); ?>:</strong> {{ data.filename }}</div>
					<div class="uploaded"><strong><?php esc_html_e( 'Date', 'unsplash' ); ?>:</strong> {{ data.dateFormatted }}</div>

					<# if ( data.filesizeHumanReadable ) { #>
					<div class="file-size"><strong><?php esc_html_e( 'File size', 'unsplash' ); ?>:</strong> {{ data.filesizeHumanReadable }}</div>
					<# } #>

					<# if ( 'image' === data.type && ! data.uploading ) { #>
						<# if ( data.width && data.height ) { #>
						<div class="dimensions">
							<strong><?php esc_html_e( 'Original dimensions', 'unsplash' ); ?>:</strong>
							<?php
							/* translators: 1: A number of pixels wide, 2: A number of pixels tall. */
							printf( esc_html__( '%1$s by %2$s pixels' ), '{{ data.width }}', '{{ data.height }}' );
							?>
						</div>
						<# } #>

						<# if ( data.originalUnsplashImageURL && data.originalUnsplashImageName ) { #>
							<strong><?php esc_html_e( 'Original image:' ); ?></strong>
							<a href="{{ data.originalUnsplashImageURL }}">{{data.originalUnsplashImageName}}</a>
						<# } else if ( data.originalImageURL && data.originalImageName ) { #>
							<strong><?php esc_html_e( 'Original image:' ); ?></strong>
							<a href="{{ data.originalImageURL }}">{{data.originalImageName}}</a>
						<# } #>

						<# if ( data.can.save && data.sizes ) { #>
							<a class="edit-attachment" href="{{ data.editLink }}&amp;image-editor" target="_blank"><?php _e( 'Edit Image' ); ?></a>
						<# } #>
					<# } #>

					<# if ( ! data.uploading && data.can.remove ) { #>
						<?php if ( MEDIA_TRASH ) : ?>
						<# if ( 'trash' === data.status ) { #>
							<button type="button" class="button-link untrash-attachment"><?php _e( 'Restore from Trash' ); ?></button>
						<# } else { #>
							<button type="button" class="button-link trash-attachment"><?php _e( 'Move to Trash' ); ?></button>
						<# } #>
						<?php else : ?>
							<button type="button" class="button-link delete-attachment"><?php _e( 'Delete Permanently' ); ?></button>
						<?php endif; ?>
					<# } #>

					<div class="compat-meta">
						<# if ( data.compat && data.compat.meta ) { #>
							{{{ data.compat.meta }}} <?php // phpcs:ignore WordPressVIPMinimum.Security.Mustache.OutputNotation ?>
						<# } #>
					</div>

				</div>
			</div>
			<# if ( 'image' === data.type ) { #>
			<span class="setting has-description" data-setting="alt">
				<label for="attachment-details-alt-text" class="name"><?php esc_html_e( 'Alt Text' ); ?></label>
				<input type="text" id="attachment-details-alt-text" value="{{ data.alt }}" aria-describedby="alt-text-description" />
			</span>
			<p class="description" id="alt-text-description"><?php echo wp_kses_post( $alt_text_description ); ?></p>
			<# } #>
			<?php if ( post_type_supports( 'attachment', 'title' ) ) : ?>
			<span class="setting" data-setting="title">
				<label for="attachment-details-title" class="name"><?php esc_html_e( 'Title' ); ?></label>
				<input type="text" id="attachment-details-title" value="{{ data.title }}" />
			</span>
			<?php endif; ?>

			<span class="setting" data-setting="caption">
				<label for="attachment-details-caption" class="name"><?php esc_html_e( 'Caption' ); ?></label>
				<textarea id="attachment-details-caption">{{ data.caption }}</textarea>
			</span>
			<span class="setting" data-setting="description">
				<label for="attachment-details-description" class="name"><?php esc_html_e( 'Description' ); ?></label>
				<textarea id="attachment-details-description">{{ data.description }}</textarea>
			</span>
			<span class="setting" data-setting="url">
				<label for="attachment-details-copy-link" class="name"><?php esc_html_e( 'Copy Link' ); ?></label>
				<input type="text" id="attachment-details-copy-link" value="{{ data.url }}" readonly />
			</span>
		</script>
		<?php
		// phpcs:enable
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
