<?php
/**
 * REST API Controller class.
 *
 * @package XWP\Unsplash\RestAPI.
 */

namespace XWP\Unsplash\RestAPI;

use Crew\Unsplash\HttpClient;
use WP_REST_Controller;
use XWP\Unsplash\RestAPI\Routes\PhotosRoute;
use XWP\Unsplash\RestAPI\Routes\SearchPhotosRoute;

/**
 * REST API Controller.
 */
class RestController extends WP_REST_Controller {

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
	 * Registers the routes for the Unsplash API.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/photos', PhotosRoute::get_options() );
		register_rest_route( $this->namespace, '/search/photos', SearchPhotosRoute::get_options() );
	}
}
