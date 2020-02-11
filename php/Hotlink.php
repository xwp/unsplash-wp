<?php
/**
 * Hotlink class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

use WP_Query;

/**
 * WordPress hotlink interface.
 */
class Hotlink {

	/**
	 * Router interface.
	 *
	 * @var Router
	 */
	protected $router;

	/**
	 * Setup the router instance.
	 *
	 * @param Router $router Instance of the router abstraction.
	 */
	public function __construct( $router ) {
		$this->router = $router;
	}

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'image_downsize', [ $this, 'image_downsize' ], 10, 3 );
		add_filter( 'wp_get_attachment_url', [ $this, 'wp_get_attachment_url' ], 10, 2 );
		add_filter( 'the_content', [ $this, 'hotlink_images_in_content' ], 99, 1 );
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
	 * Filter image downsize.
	 *
	 * @param array        $array Array.
	 * @param int          $id Attachment ID.
	 * @param array|string $size Size.
	 *
	 * @return mixed
	 */
	public function image_downsize( $array, $id, $size ) {
		$original_url = $this->get_original_url( $id );
		if ( ! $original_url ) {
			return $array;
		}
		$sizes = $this->router->image_sizes();
		if ( is_array( $size ) ) {
			list( $width, $height ) = $size;
		} elseif ( isset( $sizes[ $size ] ) ) {
			list( $width, $height ) = array_values( $sizes[ $size ] );
		} else {
			list( $width, $height ) = array_values( $sizes['thumbnail'] );
		}

		if ( ! $width || ! $height ) {
			return $array;
		}

		$original_url = $this->get_original_url_with_size( $original_url, $width, $height );
		$array        = [ $original_url, $width, $height, false ];

		return $array;
	}

	/**
	 * Filters 'img' elements in post content to add hotlinked images.
	 *
	 * @see wp_image_add_srcset_and_sizes()
	 *
	 * @param string $content The raw post content to be filtered.
	 * @return string Converted content with hotlinked images.
	 */
	function hotlink_images_in_content( $content ) {
		if ( ! preg_match_all( '/<img [^>]+>/', $content, $matches ) ) {
			return $content;
		}

		$selected_images = array();
		$attachment_ids  = array();

		foreach ( $matches[0] as $image ) {
			if ( preg_match( '/wp-image-([0-9]+)/i', $image, $class_id ) ) {
				$attachment_id = absint( $class_id[1] );

				if ( $attachment_id ) {
					/*
					 * If exactly the same image tag is used more than once, overwrite it.
					 * All identical tags will be replaced later with 'str_replace()'.
					 */
					$selected_images[ $image ] = $attachment_id;
					// Overwrite the ID when the same image is included more than once.
					$attachment_ids[ $attachment_id ] = true;
				}
			}
		}

		if ( count( $attachment_ids ) > 1 ) {
			$this->prime_post_caches( array_keys( $attachment_ids ) );
		}

		foreach ( $selected_images as $image => $attachment_id ) {
			$image_meta = wp_get_attachment_metadata( $attachment_id );
			$content    = str_replace( $image, $this->replace_image( $image, $image_meta, $attachment_id ), $content );
		}

		return $content;
	}

	/**
	 * Return inline image with hotlink images.
	 *
	 * @see wp_image_add_srcset_and_sizes()
	 *
	 * @param string $image         An HTML 'img' element to be filtered.
	 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
	 * @param int    $attachment_id Image attachment ID.
	 * @return string Converted 'img' element with 'srcset' and 'sizes' attributes added.
	 */
	public function replace_image( $image, $image_meta, $attachment_id ) {
		$original_url = $this->get_original_url( $attachment_id );
		if ( ! $original_url ) {
			return $image;
		}

		$image_src         = preg_match( '/src="([^"]+)"/', $image, $match_src ) ? $match_src[1] : '';
		list( $image_src ) = explode( '?', $image_src );

		// Return early if we couldn't get the image source.
		if ( ! $image_src ) {
			return $image;
		}

		// Bail early if an image has been inserted and later edited.
		if ( preg_match( '/-e[0-9]{13}/', $image_meta['file'], $img_edit_hash ) && false === strpos( wp_basename( $image_src ), $img_edit_hash[0] ) ) {
			return $image;
		}

		$width  = preg_match( '/ width="([0-9]+)"/', $image, $match_width ) ? (int) $match_width[1] : 0;
		$height = preg_match( '/ height="([0-9]+)"/', $image, $match_height ) ? (int) $match_height[1] : 0;

		if ( ! $width || ! $height ) {
			/*
			 * If attempts to parse the size value failed, attempt to use the image meta data to match
			 * the image file name from 'src' against the available sizes for an attachment.
			 */
			$image_filename = wp_basename( $image_src );

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

		if ( ! $width || ! $height ) {
			return $image;
		}

		$image = str_replace( $image_src, $this->get_original_url_with_size( $original_url, $width, $height ), $image );

		return $image;
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
	 * Helper function to get sized URL.
	 *
	 * @param string $url Original URL of unsplash asset.
	 * @param int    $width Width of image.
	 * @param int    $height Height of image.
	 * @param array  $attr Other attributes to be passed to the URL.
	 *
	 * @return string Format image url.
	 */
	protected function get_original_url_with_size( $url, $width, $height, $attr = [] ) {
		$attr = wp_parse_args(
			$attr,
			[
				'w' => $width,
				'h' => $height,
			]
		);
		$url  = add_query_arg(
			$attr,
			$url
		);

		return $url;
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
	protected function prime_post_caches( array $attachment_ids ) {
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

		$get_attachments = new WP_Query;
		return $get_attachments->query( $parsed_args );
	}
}
