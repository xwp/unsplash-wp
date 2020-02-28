<?php
/**
 * Utils class.
 *
 * @package Unsplash
 */

namespace XWP\Unsplash;

/**
 * Plugin Utils.
 */
class Utils {
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

		foreach ( $image_sizes as $s ) {
			if ( in_array( $s, [ 'thumbnail', 'medium', 'medium_large', 'large' ], true ) ) {
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
	 * Log an exception.
	 *
	 * @param \Exception $e Exception.
	 */
	public function log_error( \Exception $e ) {

		if ( ! constant( 'WP_DEBUG' ) ) {
			return;
		}

		$message = sprintf(
			"%1\$s: %2\$s\n%3\$s:\n%4\$s",
			__( 'Error', 'unsplash' ),
			$e->getMessage(),
			__( 'Stack Trace', 'unsplash' ),
			$e->getTraceAsString()
		);

		/**
		 * Stop IDE from complaining.
		 *
		 * @noinspection ForgottenDebugOutputInspection
		 */
		error_log( $message, $e->getCode() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}
