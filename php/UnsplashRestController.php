<?php
/**
 * Unsplash REST API Controller class.
 *
 * @package XWP\Unsplash.
 */

namespace XWP\Unsplash;

use Crew\Unsplash\HttpClient;
use Crew\Unsplash\Photo;
use Crew\Unsplash\Search;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Unsplash API REST Controller.
 */
class UnsplashRestController extends WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'unsplash/v1';

		HttpClient::init(
			[
				'applicationId' => getenv( 'UNSPLASH_APP_ID' ),
				'secret'        => getenv( 'UNSPLASH_APP_SECRET' ),
				'utmSource'     => 'WordPress-XWP',
			]
		);
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/photos',
			[
				[
					'methods'  => WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_photos' ],
					'args'     => [
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
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/search/photos',
			[
				[
					'methods'  => WP_REST_Server::READABLE,
					'callback' => [ $this, 'search_photos' ],
					'args'     => [
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
					],
				],
			]
		);
	}

	/**
	 * Retrieve a page of photos.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response Single page of photo results.
	 */
	public function get_photos( WP_REST_Request $request ) {
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );
		$order_by = $request->get_param( 'order_by' );

		return new WP_REST_Response( Photo::all( $page, $per_page, $order_by )->toArray() );
	}

	/**
	 * Retrieve a page of photos filtered by a search term.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error Single page of photo results.
	 */
	public function search_photos( WP_REST_Request $request ) {
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
