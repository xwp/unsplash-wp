<?php
/**
 * API class.
 *
 * @package Unsplash
 */

namespace Unsplash;

use WP_Error;

/**
 * Class API
 *
 * @package Unsplash
 */
class API {
	/**
	 * API credentials.
	 *
	 * @var array
	 */
	protected $credentials = [];

	/**
	 * API constructor.
	 *
	 * @param array $credentials API credentials.
	 */
	public function __construct( array $credentials ) {
		$this->credentials = $credentials;
	}

	/**
	 * Retrieve the a photo object from the ID specified.
	 *
	 * @param  string $id ID of the photo.
	 *
	 * @return Api_Response
	 */
	public function get( $id ) {
		return $this->send_request( '', [] );
	}

	/**
	 * Triggers a download for a photo.
	 *
	 * @param  string $id ID of the photo.
	 *
	 * @return Api_Response|WP_Error
	 */
	public function download( $id ) {
		return $this->send_request( '', [] );
	}

	/**
	 * Retrieve all the photos on a specific page.
	 *
	 * @param  integer $page Page from which the photos need to be retrieve.
	 * @param  integer $per_page Number of element in a page.
	 * @param string  $order_by Order in which to retrieve photos.
	 *
	 * @return Api_Response|WP_Error
	 */
	public function all( $page = 1, $per_page = 10, $order_by = 'latest' ) {
		return $this->send_request( '', [] );
	}

	/**
	 * Retrieve a single page of photo results depending on search results.
	 *
	 * @param  string  $search       Search terms.
	 * @param  integer $page         Page number to retrieve. (Optional; default: 1).
	 * @param  integer $per_page     Number of items per page. (Optional; default: 10).
	 * @param  string  $orientation  Filter search results by photo orientation. Valid values are landscape,
	 *                               portrait, and squarish. (Optional).
	 * @param  string  $collections  Collection ID(‘s) to narrow search. If multiple, comma-separated. (Optional).
	 *
	 * @return Api_Response|WP_Error
	 */
	public function search( $search, $page = 1, $per_page = 10, $orientation = null, $collections = null ) {
		return $this->send_request( '', [] );
	}

	/**
	 * Send request.
	 *
	 * @param string $path Path of the Unsplash API.
	 * @param array  $args Args passed to the url.
	 *
	 * @return Api_Response|WP_Error
	 */
	public function send_request( $path, array $args = [] ) {

		if ( 1 == 2 ) {
			return new WP_Error();
		}

		return new Api_Response();
	}
}

