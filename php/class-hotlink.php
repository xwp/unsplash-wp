<?php
/**
 * Hotlink class.
 *
 * @package Unsplash
 */

namespace Unsplash;

use WP_Query;
use WP_Post;

/**
 * WordPress hotlink interface.
 */
class Hotlink {

	/**
	 * Plugin class.
	 *
	 * @var Plugin
	 */
	public $plugin;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Instance of the plugin abstraction.
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Initiate the class.
	 */
	public function init() {
		add_action( 'attachment_submitbox_misc_actions', [ $this, 'attachment_submitbox_misc_actions' ], 11 );
		add_filter( 'wp_get_attachment_url', [ $this, 'wp_get_attachment_url' ], 10, 2 );
		add_filter( 'wp_prepare_attachment_for_js', [ $this, 'wp_prepare_attachment_for_js' ], 99, 2 );
		add_filter( 'rest_prepare_attachment', [ $this, 'rest_prepare_attachment' ], 99, 3 );
		add_filter( 'content_save_pre', [ $this, 'replace_hotlinked_images_in_content' ], 99, 1 );
		add_filter( 'the_content', [ $this, 'hotlink_images_in_content' ], 99, 1 );
		add_filter( 'wp_get_attachment_image_src', [ $this, 'wp_get_attachment_image_src' ], 10, 5 );
		add_filter( 'image_downsize', [ $this, 'image_downsize' ], 10, 3 );
		add_filter( 'wp_calculate_image_srcset', [ $this, 'wp_calculate_image_srcset' ], 99, 5 );
		add_filter( 'get_image_tag', [ $this, 'get_image_tag' ], 10, 6 );
		add_filter( 'wp_get_attachment_caption', [ $this, 'wp_get_attachment_caption' ], 10, 2 );
		add_filter( 'render_block', [ $this, 'render_block' ], 10, 2 );
		add_filter( 'wp_edited_image_metadata', [ $this, 'add_edited_attachment_metadata' ], 10, 3 );
		add_filter( 'wp_image_file_matches_image_meta', [ $this, 'make_unsplash_images_cropable' ], 10, 4 );
	}

	/**
	 * Filter wp_get_attachment_url
	 *
	 * @param string $url Original URL.
	 * @param int    $id Attachment ID.
	 *
	 * @return mixed
	 */
	public function wp_get_attachment_url( $url, $id ) {
		$unsplash_url = $this->get_unsplash_url( $id );
		$cropped      = $this->is_cropped_image( $id );
		if ( ! $unsplash_url || $cropped ) {
			return $url;
		}

		return $unsplash_url;
	}

	/**
	 * Retrieve the unfiltered URL for an attachment.
	 *
	 * @param  int $attachment_id Attachment ID.
	 * @return string|false Unfiltered Attachment URL, otherwise false.
	 */
	public function get_attachment_url( $attachment_id ) {
		remove_filter( 'wp_get_attachment_url', [ $this, 'wp_get_attachment_url' ], 10 );
		$url = wp_get_attachment_url( $attachment_id );
		add_filter( 'wp_get_attachment_url', [ $this, 'wp_get_attachment_url' ], 10, 2 );
		return $url;
	}

	/**
	 * Add unsplash image sizes to admin ajax.
	 *
	 * @param array   $response Data for admin ajax.
	 * @param WP_Post $attachment Attachment object.
	 *
	 * @return mixed
	 */
	public function wp_prepare_attachment_for_js( array $response, $attachment ) {
		if ( ! $attachment instanceof WP_Post ) {
			return $response;
		}
		$unsplash_url = $this->get_unsplash_url( $attachment->ID );
		$cropped      = $this->is_cropped_image( $attachment->ID );
		if ( ! $unsplash_url || $cropped ) {
			return $response;
		}
		$response['sizes'] = $this->plugin->add_image_sizes( $unsplash_url, $response['width'], $response['height'] );
		// We always have the full sized image.
		$url               = $this->get_attachment_url( $attachment->ID );
		$response['url']   = $url;
		$response['sizes'] = $this->change_full_url( $response['sizes'], 'url', $url );

		$link = get_post_meta( $attachment->ID, 'original_link', true );
		if ( $link ) {
			$response['originalUnsplashImageName'] = esc_html__( 'Unsplash', 'unsplash' );
			$response['originalUnsplashImageURL']  = $link;
		}

		return $response;
	}

	/**
	 * Add unsplash image sizes to REST API.
	 *
	 * @param WP_REST_Response $wp_response The response object.
	 * @param WP_Post          $attachment  The original attachment post.
	 * @param WP_REST_Request  $wp_request  Request used to generate the response.
	 *
	 * @return mixed
	 */
	public function rest_prepare_attachment( $wp_response, $attachment, $wp_request ) {
		if ( ! $attachment instanceof WP_Post ) {
			return $wp_response;
		}
		$unsplash_url = $this->get_unsplash_url( $attachment->ID );
		$cropped      = $this->is_cropped_image( $attachment->ID );
		if ( ! $unsplash_url || $cropped ) {
			return $wp_response;
		}

		$url = $this->get_attachment_url( $attachment->ID );

		$response = $wp_response->get_data();
		if ( isset( $response['media_details'] ) ) {
			$response['media_details']['sizes'] = $this->plugin->add_image_sizes( $unsplash_url, $response['media_details']['width'], $response['media_details']['height'] );
			// Reformat image sizes as REST API response is a little differently formatted.
			$response['media_details']['sizes'] = $this->change_fields( $response['media_details']['sizes'], $response['media_details']['file'] );
			// We always have the full sized image.
			$response['media_details']['sizes'] = $this->change_full_url( $response['media_details']['sizes'], 'source_url', $url );
			// No image sizes missing.
			if ( isset( $response['missing_image_sizes'] ) ) {
				$response['missing_image_sizes'] = [];
			}
		}

		// Return raw image url in REST API.
		if ( isset( $response['source_url'] ) ) {
			$response['source_url'] = $url;
		}

		$link = get_post_meta( $attachment->ID, 'original_link', true );
		if ( $link ) {
			$response['originalUnsplashImageName'] = esc_html__( 'Unsplash', 'unsplash' );
			$response['originalUnsplashImageURL']  = $link;
		}

		$context = ! empty( $wp_request['context'] ) ? $wp_request['context'] : 'view';
		if ( 'edit' === $context ) {
			$response['nonces'] = [
				'update' => false,
				'delete' => false,
				'edit'   => false,
			];
			if ( current_user_can( 'edit_post', $attachment->ID ) ) {
				$response['nonces']['update'] = wp_create_nonce( 'update-post_' . $attachment->ID );
				$response['nonces']['edit']   = wp_create_nonce( 'image_editor-' . $attachment->ID );
			}

			if ( current_user_can( 'delete_post', $attachment->ID ) ) {
				$response['nonces']['delete'] = wp_create_nonce( 'delete-post_' . $attachment->ID );
			}
		}

		$wp_response->set_data( $response );

		return $wp_response;
	}

	/**
	 * Add unsplash original link to attachment edit page.
	 */
	public function attachment_submitbox_misc_actions() {
		$post          = get_post();
		$attachment_id = $post->ID;
		$unsplash_url  = $this->get_unsplash_url( $attachment_id );
		$cropped       = $this->is_cropped_image( $attachment_id );
		if ( ! $unsplash_url || $cropped ) {
			return;
		}
		$link = get_post_meta( $attachment_id, 'original_link', true );
		if ( $link ) {
			?>
			<div class="misc-pub-section misc-pub-original-unsplash-image">
				<?php esc_html_e( 'Original image:', 'unsplash' ); ?>
				<a href="<?php echo esc_url( $link ); ?>">
					<?php esc_html_e( 'Unsplash', 'unsplash' ); ?>
				</a>
			</div>
			<style type="text/css">
				.misc-pub-section.misc-pub-original-image{
					display: none;
				}
			</style>
			<?php
		}
	}

	/**
	 * Reformat image sizes as REST API response is a little differently formatted.
	 *
	 * @param array  $sizes List of sizes.
	 * @param String $file  File name.
	 * @return array
	 */
	public function change_fields( array $sizes, $file ) {
		foreach ( $sizes as $size => $details ) {
			$details['file']       = $file;
			$details['source_url'] = $details['url'];
			$details['mime_type']  = 'image/jpeg';
			unset( $details['url'], $details['orientation'] );
			$sizes[ $size ] = $details;
		}

		return $sizes;
	}

	/**
	 * Helper function to replace full image url.
	 *
	 * @param  array  $sizes Array of sizes.
	 * @param  string $field Field to replace.
	 * @param  string $url   URL to replace.
	 * @return array   Array of sizes.
	 */
	public function change_full_url( array $sizes, $field, $url ) {
		$sizes['full'][ $field ] = $url;
		return $sizes;
	}

	/**
	 * Filter image downsize.
	 *
	 * @param array        $should_resize Array.
	 * @param int          $id Attachment ID.
	 * @param array|string $size Size.
	 *
	 * @return mixed
	 */
	public function image_downsize( $should_resize, $id, $size ) {
		$unsplash_url = $this->get_unsplash_url( $id );
		$cropped      = $this->is_cropped_image( $id );
		if ( ! $unsplash_url || $cropped ) {
			return $should_resize;
		}
		$image_meta = wp_get_attachment_metadata( $id );
		$image_size = ( isset( $image_meta['sizes'] ) ) ? $image_meta['sizes'] : [];
		$sizes      = $this->plugin->image_sizes();
		$fit        = 'crop';
		if ( is_array( $size ) ) {
			// If array is passed, just use height and width.
			list( $width, $height ) = $size;
		} elseif ( isset( $image_size[ $size ] ) ) {
			// Get generated size from post meta.
			$height = isset( $image_size[ $size ]['height'] ) ? $image_size[ $size ]['height'] : 0;
			$width  = isset( $image_size[ $size ]['width'] ) ? $image_size[ $size ]['width'] : 0;
		} elseif ( isset( $sizes[ $size ] ) ) {
			// Get defined size.
			list( $width, $height, $crop ) = array_values( $sizes[ $size ] );
			if ( ! $crop ) {
				$fit = false;
			}
		} else {
			// If can't find image size, then use full size.
			$height = isset( $image_meta['height'] ) ? $image_meta['height'] : 0;
			$width  = isset( $image_meta['width'] ) ? $image_meta['width'] : 0;
		}

		if ( ! $width || ! $height ) {
			return $should_resize;
		}

		$unsplash_url = $this->plugin->get_original_url_with_size( $unsplash_url, $width, $height, [ 'fit' => $fit ] );

		return [ $unsplash_url, $width, $height, false ];
	}

	/**
	 * Work around for image preview in Attachment screen.
	 *
	 * @param array|false  $image rray of image data, or boolean false if no image is available.
	 * @param int          $attachment_id Image attachment ID.
	 * @param string|int[] $size          Requested size of image. Image size name, or array of width
	 *                                    and height values (in that order).
	 * @param bool         $icon          Whether the image should be treated as an icon.
	 */
	public function wp_get_attachment_image_src( $image, $attachment_id, $size, $icon ) {
		if ( is_array( $size ) && array( 900, 450 ) === $size && $icon ) {
			$unsplash_url = $this->get_unsplash_url( $attachment_id );
			$cropped      = $this->is_cropped_image( $attachment_id );
			if ( ! $unsplash_url || $cropped ) {
				return $image;
			}
			$request_width  = 900;
			$image_meta     = wp_get_attachment_metadata( $attachment_id );
			$height         = absint( $image_meta['height'] );
			$width          = absint( $image_meta['width'] );
			$request_height = $this->plugin->get_image_height( $width, $height, $request_width );
			$image          = [
				$this->plugin->get_original_url_with_size( $unsplash_url, $request_width, $request_height, [ 'fit' => false ] ),
				$request_width,
				$request_height,
			];
		}

		return $image;
	}

	/**
	 * Retrieve all image tags from content.
	 *
	 * @param string $content Content.
	 * @return array Array consisting of the image tag and src URL, keyed by the attachment ID.
	 */
	public function get_attachments_from_content( $content ) {
		// Get all <img> tags with a 'src' attribute.
		if ( ! preg_match_all( '#<img.+?src="(?P<url>.+?)".+?/>#', wp_unslash( $content ), $matches ) ) {
			return [];
		}

		$selected_images = [];

		foreach ( $matches[0] as $key => $image_tag ) {
			if ( preg_match( '/wp-image-([0-9]+)/i', $image_tag, $class_id ) ) {
				$attachment_id = absint( $class_id[1] );

				if ( $attachment_id ) {
					/*
					 * If exactly the same image tag is used more than once, overwrite it.
					 * All identical tags will be replaced later with 'str_replace()'.
					 */
					$selected_images[] = [
						'tag' => $image_tag,
						'url' => $matches['url'][ $key ],
						'id'  => $attachment_id,
					];
				}
			}
		}

		return $selected_images;
	}

	/**
	 * Replace hotlinked image URLs in content with ones from WordPress.
	 *
	 * @param string $content Content.
	 * @return string Converted content with local images.
	 */
	public function replace_hotlinked_images_in_content( $content ) {
		$attachments = $this->get_attachments_from_content( $content );

		if ( count( $attachments ) > 1 ) {
			$this->prime_post_caches( wp_list_pluck( $attachments, 'id' ) );
		}
		remove_filter( 'wp_get_attachment_url', [ $this, 'wp_get_attachment_url' ], 10 );
		remove_filter( 'image_downsize', [ $this, 'image_downsize' ], 10 );
		foreach ( $attachments as $img_data ) {
			if ( ! strpos( $img_data['url'], 'images.unsplash.com' ) ) {
				continue;
			}

			$unsplash_url = $this->get_unsplash_url( $img_data['id'] );
			if ( ! $unsplash_url ) {
				continue;
			}
			list( $width, $height ) = $this->get_image_size_from_url( $img_data['url'] );
			if ( ! $width || ! $height ) {
				list( $width, $height ) = $this->get_image_size( $img_data['tag'], $img_data['url'], $img_data['id'] );
			}
			$wordpress_url = false;
			if ( $width && $height ) {
				$wordpress_src = wp_get_attachment_image_src( $img_data['id'], [ $width, $height ] );
				if ( is_array( $wordpress_src ) ) {
					$wordpress_url = array_shift( $wordpress_src );
				}
			}

			if ( ! $wordpress_url ) {
				$wordpress_url = wp_get_attachment_url( $img_data['id'] );
			}

			if ( $wordpress_url ) {
				$content = str_replace( $img_data['url'], $wordpress_url, $content );
			}
		}
		add_filter( 'wp_get_attachment_url', [ $this, 'wp_get_attachment_url' ], 10, 2 );
		add_filter( 'image_downsize', [ $this, 'image_downsize' ], 10, 3 );
		return $content;
	}

	/**
	 * Filters 'img' elements in post content to add hotlinked images.
	 *
	 * @see wp_image_add_srcset_and_sizes()
	 *
	 * @param string $content The raw post content to be filtered.
	 *
	 * @return string Converted content with hotlinked images.
	 */
	public function hotlink_images_in_content( $content ) {
		$attachments = $this->get_attachments_from_content( $content );

		if ( count( $attachments ) > 1 ) {
			$this->prime_post_caches( wp_list_pluck( $attachments, 'id' ) );
		}

		foreach ( $attachments as $img_data ) {
			$content = str_replace( $img_data['tag'], $this->replace_image( $img_data['tag'], $img_data['url'], $img_data['id'] ), $content );
		}

		return $content;
	}

	/**
	 * Filter source sets to give hotlink images.
	 *
	 * @param array  $sources {
	 *     One or more arrays of source data to include in the 'srcset'.
	 *
	 *     @type array $width {
	 *         @type string $url        The URL of an image source.
	 *         @type string $descriptor The descriptor type used in the image candidate string,
	 *                                  either 'w' or 'x'.
	 *         @type int    $value      The source width if paired with a 'w' descriptor, or a
	 *                                  pixel density value if paired with an 'x' descriptor.
	 *     }
	 * }
	 * @param array  $size_array {
	 *     An array of requested width and height values.
	 *
	 *     @type int $0 The width in pixels.
	 *     @type int $1 The height in pixels.
	 * }
	 * @param string $image_src     The 'src' of the image.
	 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
	 * @param int    $attachment_id Image attachment ID or 0.
	 *
	 * @return array Converted images url in an array.
	 */
	public function wp_calculate_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		unset( $size_array, $image_src );

		$unsplash_url = $this->get_unsplash_url( $attachment_id );
		$cropped      = $this->is_cropped_image( $attachment_id );
		if ( ! $unsplash_url || $cropped ) {
			return $sources;
		}

		$height = absint( $image_meta['height'] );
		$width  = absint( $image_meta['width'] );

		$new_sources = [];
		if ( ! empty( $image_meta['sizes'] ) ) {
			foreach ( $image_meta['sizes'] as $name => $value ) {
				$new_height                = absint( $value['height'] );
				$new_width                 = absint( $value['width'] );
				$_height                   = $this->plugin->get_image_height( $width, $height, $new_width, $new_height );
				$new_sources[ $new_width ] = [
					'url'        => $this->plugin->get_original_url_with_size( $unsplash_url, $new_width, $_height ),
					'descriptor' => 'w',
					'value'      => $new_width,
				];
			}
		}

		return $new_sources;
	}

	/**
	 * Return inline image with hotlink images.
	 *
	 * @see wp_image_add_srcset_and_sizes()
	 *
	 * @param string $img_tag An HTML 'img' element to be filtered.
	 * @param string $img_src Image URL.
	 * @param int    $attachment_id Image attachment ID.
	 *
	 * @return string Converted 'img' element with 'srcset' and 'sizes' attributes added.
	 */
	public function replace_image( $img_tag, $img_src, $attachment_id ) {
		$unsplash_url = $this->get_unsplash_url( $attachment_id );
		$cropped      = $this->is_cropped_image( $attachment_id );
		if ( ! $unsplash_url || $cropped ) {
			return $img_tag;
		}

		list( $width, $height ) = $this->get_image_size( $img_tag, $img_src, $attachment_id );

		if ( ! $width || ! $height ) {
			return $img_tag;
		}

		$new_src = $this->plugin->get_original_url_with_size( $unsplash_url, $width, $height );
		return str_replace( $img_src, $new_src, $img_tag );
	}

	/**
	 * Get image size.
	 *
	 * @param string $img_tag       An HTML 'img' element to be filtered.
	 * @param string $img_src       Image URL.
	 * @param int    $attachment_id Image attachment ID.
	 *
	 * @return array Array with width and height.
	 */
	public function get_image_size( $img_tag, $img_src, $attachment_id ) {
		$image_meta = wp_get_attachment_metadata( $attachment_id );
		// Bail early if an image has been inserted and later edited.
		if ( $image_meta && preg_match( '/-e[0-9]{13}/', $image_meta['file'], $img_edit_hash ) && false === strpos( wp_basename( $img_src ), $img_edit_hash[0] ) ) {
			return [];
		}

		$width  = preg_match( '/ width="([0-9]+)"/', $img_tag, $match_width ) ? (int) $match_width[1] : 0;
		$height = preg_match( '/ height="([0-9]+)"/', $img_tag, $match_height ) ? (int) $match_height[1] : 0;

		if ( ! $width || ! $height ) {
			/*
			 * If attempts to parse the size value failed, attempt to use the image meta data to match
			 * the image file name from 'src' against the available sizes for an attachment.
			 */
			list( $image_src_without_params ) = explode( '?', $img_src );
			$image_filename                   = wp_basename( $image_src_without_params );

			if ( wp_basename( $image_meta['file'] ) === $image_filename ) {
				$width  = (int) $image_meta['width'];
				$height = (int) $image_meta['height'];
			} else {
				foreach ( $image_meta['sizes'] as $image_size_data ) {
					if ( $image_filename === $image_size_data['file'] ) {
						$width  = (int) $image_size_data['width'];
						$height = (int) $image_size_data['height'];
						break;
					}
				}
			}
		}

		return [ $width, $height ];
	}

	/**
	 * Get height and width from URL.
	 *
	 * @param string $url URL Current URL of image.
	 * @return array Array with width and height.
	 */
	public function get_image_size_from_url( $url ) {
		$width  = 0;
		$height = 0;
		$url    = str_replace( '&amp;', '&', $url );
		$query  = wp_parse_url( $url, PHP_URL_QUERY );

		if ( $query ) {
			parse_str( $query, $args );

			if ( isset( $args['w'] ) ) {
				$width = (int) $args['w'];
			}

			if ( isset( $args['h'] ) ) {
				$height = (int) $args['h'];
			}
		}

		return [ $width, $height ];
	}

	/**
	 * Helper to get original url from post meta.
	 *
	 * @param int $id Attachment ID.
	 *
	 * @return string|bool URL or false is not found.
	 */
	public function get_unsplash_url( $id ) {
		return get_post_meta( $id, 'original_url', true );
	}

	/**
	 * Is cropped image.
	 *
	 * @param int $id Attachment ID.
	 *
	 * @return boolean Is cropped image.
	 */
	public function is_cropped_image( $id ) {
		return (bool) get_post_meta( $id, '_wp_attachment_backup_sizes', true );
	}


	/**
	 * Warm the object cache with post and meta information for all found
	 * images to avoid making individual database calls.
	 *
	 * @see https://core.trac.wordpress.org/ticket/40490
	 *
	 * @param array $attachment_ids Array of attachment ids.
	 *
	 * @return mixed
	 */
	public function prime_post_caches( array $attachment_ids ) {
		$parsed_args = [
			'post__in'               => $attachment_ids,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'post_status'            => 'any',
			'post_type'              => 'attachment',
			'suppress_filters'       => false,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => true,
			'nopaging'               => true, // phpcs:ignore WordPressVIPMinimum.Performance.NoPaging.nopaging_nopaging
			'orderby'                => 'post__in',
		];

		$get_attachments = new WP_Query();
		return $get_attachments->query( $parsed_args );
	}

	/**
	 * The image `src` attribute is escaped when retrieving the image tag which can mangle the `w` and `h` params we
	 * add, so the change is reverted here.
	 *
	 * @param string       $html  HTML content for the image.
	 * @param int          $id    Attachment ID.
	 * @param string       $alt   Image description for the alt attribute.
	 * @param string       $title Image description for the title attribute.
	 * @param string       $align Part of the class name for aligning the image.
	 * @param string|array $size  Size of image. Image size or array of width and height values (in that order).
	 *                            Default 'medium'.
	 *
	 * @return string Image tag.
	 */
	public function get_image_tag( $html, $id, $alt, $title, $align, $size ) {
		// Verify it is an Unsplash ID.
		$unsplash_url = $this->get_unsplash_url( $id );
		$cropped      = $this->is_cropped_image( $id );
		if ( ! $unsplash_url || $cropped ) {
			return $html;
		}

		// Replace img src.
		list( $img_src ) = image_downsize( $id, $size );
		return preg_replace( '/src="([^"]+)"/', "src=\"{$img_src}\"", $html, 1 );
	}

	/**
	 * Remove html for captions, as some themes esc_html captions before displaying.
	 *
	 * @param string $caption       Caption for the given attachment.
	 * @param int    $attachment_id Attachment ID.
	 *
	 * @return string Caption for the given attachment with html removed.
	 */
	public function wp_get_attachment_caption( $caption, $attachment_id ) {
		$unsplash_url = $this->get_unsplash_url( $attachment_id );
		if ( ! $unsplash_url ) {
			return $caption;
		}


		return wp_strip_all_tags( $caption );
	}

	/**
	 * Filters the content of a single block.
	 *
	 * @param string $block_content The block content about to be appended.
	 * @param array  $block         The full block, including name and attributes.
	 *
	 * @return string $block_content Filtered block content.
	 */
	public function render_block( $block_content, $block ) {
		if ( 'core/cover' === $block['blockName'] && isset( $block['attrs']['id'] ) ) {

			$unsplash_url = $this->get_unsplash_url( $block['attrs']['id'] );
			if ( $unsplash_url ) {
				$block_content = str_replace( $block['attrs']['url'], $unsplash_url, $block_content );
			}
		}

		return $block_content;
	}

	/**
	 * Add Unsplash metadata for edited attachment
	 *
	 * @param array $data              Array of updated attachment meta data.
	 * @param int   $new_attachment_id Attachment post ID.
	 * @param int   $attachment_id     Original Attachment post ID.
	 *
	 * @return array
	 */
	public function add_edited_attachment_metadata( $data, $new_attachment_id, $attachment_id ) {
		// Verify it is an Unsplash ID.
		$unsplash_url = $this->get_unsplash_url( $attachment_id );
		$cropped      = $this->is_cropped_image( $attachment_id );

		if ( $unsplash_url && ! $cropped ) {
			add_post_meta( $new_attachment_id, 'original_attachment_id', $attachment_id, true );
			add_post_meta( $new_attachment_id, 'original_id', get_post_meta( $attachment_id, 'original_id', true ), true );
			add_post_meta( $new_attachment_id, 'original_link', get_post_meta( $attachment_id, 'original_link', true ), true );
		}

		return $data;
	}

	/**
	 * Filter whether an image path or URI matches image meta.
	 *
	 * @param bool   $match          Whether the image relative path from the image meta
	 *                               matches the end of the URI or path to the image file.
	 * @param string $image_location Full path or URI to the tested image file.
	 * @param array  $image_meta     (Unused) The image meta data as returned by 'wp_get_attachment_metadata()'.
	 * @param int    $attachment_id  The image attachment ID or 0 if not supplied.
	 *
	 * @return bool Can an image cropable.
	 */
	public function make_unsplash_images_cropable( $match, $image_location, $image_meta, $attachment_id ) {
		$unsplash_url = $this->get_unsplash_url( $attachment_id );
		$cropped      = $this->is_cropped_image( $attachment_id );
		$is_unsplash  = strpos( $image_location, 'images.unsplash.com' );
		if ( ! $unsplash_url || $cropped || ( false === $is_unsplash ) ) {
			return $match;
		}

		return true;
	}
}
