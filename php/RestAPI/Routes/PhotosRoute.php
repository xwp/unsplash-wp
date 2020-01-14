<?php
/**
 * Photos Route class.
 *
 * @package XWP\Unsplash\RestAPI\Routes
 */

namespace XWP\Unsplash\RestAPI\Routes;

use Crew\Unsplash\Photo;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Get Unsplash photos.
 */
class PhotosRoute extends Route {

	/**
	 * Parameter arguments for `GET` request.
	 *
	 * @return array
	 */
	public static function read_args() {
		return [
			'per_page' => [
				'default'           => 10,
				'validate_callback' => static function( $param ) {
					return is_numeric( $param ) && $param <= 30;
				},
			],
			'page'     => [
				'default'           => 1,
				'validate_callback' => static function( $param ) {
					return is_numeric( $param );
				},
			],
			'order_by' => [
				'default'           => 'latest',
				'validate_callback' => static function( $param ) {
					return in_array( $param, [ 'latest', 'oldest', 'popular' ], true );
				},
			],
		];
	}

	/**
	 * Retrieve a page of photos.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response Single page of photo results.
	 */
	public static function read( WP_REST_Request $request ) {
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );
		$order_by = $request->get_param( 'order_by' );

		return new WP_REST_Response( Photo::all( $page, $per_page, $order_by )->toArray() );
	}
}
