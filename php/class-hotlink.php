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
		$this->plugin->add_doc_hooks( $this );
		// Hook these filters this way to make them unhookable.
		add_filter( 'wp_get_attachment_url', [ $this, 'wp_get_attachment_url' ], 10, 2 );
		add_filter( 'image_downsize', [ $this, 'image_downsize' ], 10, 3 );
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
		$original_url = $this->get_original_url( $id );
		if ( ! $original_url ) {
			return $url;
		}

		return $original_url;
	}

	/**
	 * Add unsplash image sizes to admin ajax.
	 *
	 * @param array   $response Data for admin ajax.
	 * @param WP_Post $attachment Attachment object.
	 *
	 * @filter wp_prepare_attachment_for_js, 99, 2
	 *
	 * @return mixed
	 */
	public function wp_prepare_attachment_for_js( array $response, $attachment ) {
		if ( ! $attachment instanceof WP_Post ) {
			return $response;
		}
		$original_url = $this->get_original_url( $attachment->ID );
		if ( ! $original_url ) {
			return $response;
		}
		$response['sizes'] = $this->plugin->add_image_sizes( $original_url, $response['width'], $response['height'] );


		return $response;
	}

	/**
	 * Add unsplash image sizes to REST API.
	 *
	 * @param WP_Response $wp_response Data for REST API.
	 * @param WP_Post     $attachment Attachment object.
	 *
	 * @filter rest_prepare_attachment, 99, 2
	 *
	 * @return mixed
	 */
	public function rest_prepare_attachment( $wp_response, $attachment ) {
		if ( ! $attachment instanceof WP_Post ) {
			return $wp_response;
		}
		$original_url = $this->get_original_url( $attachment->ID );
		if ( ! $original_url ) {
			return $wp_response;
		}
		$response = $wp_response->get_data();
		if ( isset( $response['media_details'] ) ) {
			$response['media_details']['sizes'] = $this->plugin->add_image_sizes( $original_url, $response['media_details']['width'], $response['media_details']['height'] );
			// Reformat image sizes as REST API response is a little differently formatted.
			$response['media_details']['sizes'] = $this->change_fields( $response['media_details']['sizes'], $response['media_details']['file'] );
			// No image sizes missing.
			if ( isset( $response['missing_image_sizes'] ) ) {
				$response['missing_image_sizes'] = [];
			}
		}

		// Return raw image url in REST API.
		if ( isset( $response['source_url'] ) ) {
			remove_filter( 'wp_get_attachment_url', [ $this, 'wp_get_attachment_url' ], 10 );
			$response['source_url'] = wp_get_attachment_url( $attachment->ID );
			add_filter( 'wp_get_attachment_url', [ $this, 'wp_get_attachment_url' ], 10, 2 );
		}

		$wp_response->set_data( $response );

		return $wp_response;
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
	 * Filter image downsize.
	 *
	 * @param array        $should_resize Array.
	 * @param int          $id Attachment ID.
	 * @param array|string $size Size.
	 *
	 * @return mixed
	 */
	public function image_downsize( $should_resize, $id, $size ) {
		$original_url = $this->get_original_url( $id );
		if ( ! $original_url ) {
			return $should_resize;
		}
		$image_meta = wp_get_attachment_metadata( $id );
		$image_size = ( isset( $image_meta['sizes'] ) ) ? $image_meta['sizes'] : [];
		$sizes      = $this->plugin->image_sizes();
		if ( is_array( $size ) ) {
			// If array is passed, just use height and width.
			list( $width, $height ) = $size;
		} elseif ( isset( $image_size[ $size ] ) ) {
			// Get generated size from post meta.
			$height = isset( $image_size[ $size ]['height'] ) ? $image_size[ $size ]['height'] : 0;
			$width  = isset( $image_size[ $size ]['width'] ) ? $image_size[ $size ]['width'] : 0;
		} elseif ( isset( $sizes[ $size ] ) ) {
			// Get defined size.
			list( $width, $height ) = array_values( $sizes[ $size ] );
		} else {
			// If can't find image size, then use full size.
			$height = isset( $image_meta['height'] ) ? $image_meta['height'] : 0;
			$width  = isset( $image_meta['width'] ) ? $image_meta['width'] : 0;

		}

		if ( ! $width || ! $height ) {
			return $should_resize;
		}

		$original_url = $this->plugin->get_original_url_with_size( $original_url, $width, $height );

		return [ $original_url, $width, $height, false ];
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
	 * @filter content_save_pre, 99, 1
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

			$original_url = $this->get_original_url( $img_data['id'] );
			if ( ! $original_url ) {
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
	 * @filter the_content, 99, 1
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
		$original_url = $this->get_original_url( $attachment_id );
		if ( ! $original_url ) {
			return $img_tag;
		}

		list( $width, $height ) = $this->get_image_size( $img_tag, $img_src, $attachment_id );

		if ( ! $width || ! $height ) {
			return $img_tag;
		}

		$new_src = $this->plugin->get_original_url_with_size( $original_url, $width, $height );
		return str_replace( $img_src, $new_src, $img_tag );
	}

	/**
	 * Get image size.
	 *
	 * @param string $img_tag An HTML 'img' element to be filtered.
	 * @param string $img_src Image URL.
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
	protected function get_original_url( $id ) {
		return get_post_meta( $id, 'original_url', true );
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
	 * @filter get_image_tag, 10, 6
	 *
	 * @return string Image tag.
	 */
	public function get_image_tag( $html, $id, $alt, $title, $align, $size ) {
		// Verify it is an Unsplash ID.
		$original_url = $this->get_original_url( $id );
		if ( ! $original_url ) {
			return $html;
		}

		// Replace img src.
		list( $img_src ) = image_downsize( $id, $size );
		return preg_replace( '/src="([^"]+)"/', "src=\"{$img_src}\"", $html, 1 );
	}

	/**
	 * Remove html for captions, as some themes esc_html captions before displaying.
	 *
	 * @filter wp_get_attachment_caption, 10, 2
	 *
	 * @param string $caption Caption for the given attachment.
	 * @param int    $post_id Attachment ID.
	 * @return string  Caption for the given attachment with html removed.
	 */
	public function wp_get_attachment_caption( $caption, $attachment_id ){
		$original_url = $this->get_original_url( $attachment_id );
		if ( ! $original_url ) {
			return $caption;
		}

		return wp_strip_all_tags( $caption );
	}
}
