<?php
/**
 * Search Photos Route class.
 *
 * @package XWP\Unsplash\RestAPI\Routes
 */

namespace XWP\Unsplash\RestAPI\Routes;

use Crew\Unsplash\Search;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Search Unsplash photos by query.
 */
class SearchPhotosRoute extends Route {

	/**
	 * Parameter arguments for `GET` request.
	 *
	 * @return array
	 */
	public static function read_args() {
		return [
			'query'       => [
				'required'          => true,
				'sanitize_callback' => static function( $param ) {
					return sanitize_text_field( $param );
				},
				'validate_callback' => static function( $param ) {
					return ! empty( $param );
				},
			],
			'page'        => [
				'default'           => 1,
				'validate_callback' => static function( $param ) {
					return is_numeric( $param );
				},
			],
			'per_page'    => [
				'default'           => 10,
				'validate_callback' => static function( $param ) {
					return is_numeric( $param ) && $param <= 30;
				},
			],
			'orientation' => [
				'default'           => null,
				'validate_callback' => static function( $param ) {
					return ! empty( $param ) && in_array( $param, [ 'landscape', 'portrait', 'squarish' ], true );
				},
			],
			'collections' => [
				'default'           => null,
				'validate_callback' => static function( $param ) {
					return ! empty( $param );
				},
			],
		];
	}

	/**
	 * Retrieve a page of photos filtered by a search term.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error Single page of photo results.
	 */
	public static function read( WP_REST_Request $request ) {
		$search      = $request->get_param( 'query' );
		$page        = $request->get_param( 'page' );
		$per_page    = $request->get_param( 'per_page' );
		$orientation = $request->get_param( 'orientation' );
		$collections = $request->get_param( 'collections' );

		try {
			$response = Search::photos( $search, $page, $per_page, $orientation, $collections )->getArrayObject()->toArray();
		} catch ( \Exception $e ) {
			$response = new WP_Error( 'search-photos', __( 'An unknown error occurred while searching for a photo', 'unsplash' ), [ 'status' => '500' ] );

			/**
			 * Stop IDE from complaining.
			 *
			 * @noinspection ForgottenDebugOutputInspection
			 */
			error_log( $e->getMessage(), $e->getCode() );
		}

		return $response;
	}
}
