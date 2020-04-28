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
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	public $plugin;

	/**
	 * API constructor.
	 *
	 * @param Plugin $plugin Instance of the plugin abstraction.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	/**
	 * Retrieve the a photo object from the ID specified.
	 *
	 * @param string $id ID of the photo.
	 * @param bool   $trigger_download If a request is fired to count a donwload.
	 *
	 * @return array|Api_Response|WP_Error
	 */
	public function get( $id, $trigger_download = false ) {
		$request = $this->send_request( "/photos/{$id}", [] );

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		if ( $trigger_download ) {
			// Make a remote, uncached and unblocking call to the download endpoint.
			$this->get_remote( $request['body']['links']['download_location'], [ 'blocking' => false ] );
		}
		return new Api_Response( $request['body'], 1, 1, $request['cached'] );
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
		$request = $this->send_request(
			'/photos',
			[
				'page'     => $page,
				'per_page' => $per_page,
				'order_by' => $order_by,
			]
		);
		if ( is_wp_error( $request ) ) {
			return $request;
		}
		return new Api_Response( $request['body'], $request['headers']['total_pages'], $request['headers']['total'], $request['cached'] );
	}

	/**
	 * Retrieve a single page of photo results depending on search results.
	 *
	 * @param  string  $query       Search terms.
	 * @param  integer $page         Page number to retrieve. (Optional; default: 1).
	 * @param  integer $per_page     Number of items per page. (Optional; default: 10).
	 * @param  string  $orientation  Filter search results by photo orientation. Valid values are landscape,
	 *                               portrait, and squarish. (Optional).
	 * @param  string  $collections  Collection ID(‘s) to narrow search. If multiple, comma-separated. (Optional).
	 *
	 * @return Api_Response|WP_Error
	 */
	public function search( $query, $page = 1, $per_page = 10, $orientation = null, $collections = null ) {
		$args = [
			'query'    => $query,
			'page'     => $page,
			'per_page' => $per_page,
		];

		if ( ! empty( $orientation ) ) {
			$args['orientation'] = $orientation;
		}

		if ( ! empty( $collections ) ) {
			$args['collections'] = $collections;
		}

		$request = $this->send_request( '/search/photos', $args );

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		return new Api_Response( $request['body']['results'], $request['headers']['total_pages'], $request['headers']['total'], $request['cached'] );
	}

	/**
	 * Send request.
	 *
	 * @param string $path Path of the Unsplash API.
	 * @param array  $args Args passed to the url.
	 *
	 * @return Array|WP_Error
	 */
	public function send_request( $path, array $args = [] ) {
		$url               = 'https://api.unsplash.com' . $path;
		$credentials       = $this->plugin->settings->get_credentials();
		$args['client_id'] = $credentials['applicationId'];
		$cache             = new Api_Cache( $path, $args );
		$cache_value       = $cache->get_cache();

		if ( is_array( $cache_value ) ) {
			$cache_value['cached'] = true;
			return $cache_value;
		}
		$url = add_query_arg( $args, $url );

		/**
		 * Filter the request URL Valid.
		 *
		 * @param string $url URL to requested.
		 * @param string $path Path of the Unsplash API.
		 * @param array  $args Args passed to the url.
		 *
		 * @return string $url Filtered URL.
		 */
		$url = apply_filters( 'unsplash_request_url', $url, $path, $args );

		$response = $this->get_remote( $url );

		// If wp_remote_get returns an error, return a formatted error.
		if ( is_wp_error( $response ) ) {
			return $this->format_exception( $response->get_error_code(), 400 );
		}

		$code = wp_remote_retrieve_response_code( $response );
		// If error state is returned, then return an error.
		if ( 200 !== $code ) {
			return $this->format_exception( 'unsplash_api_error', $code );
		}

		$body = wp_remote_retrieve_body( $response );
		// If API limit is reached, then return an error.
		if ( 'Rate Limit Exceeded' === $body ) {
			return $this->format_exception( 'unsplash_rate_limit', 429 );
		}

		$body = json_decode( $body, true );
		// There is a json decode error, there return an error.
		if ( ! is_array( $body ) ) {
			return $this->format_exception( 'invalid_unsplash_response', 400 );
		}
		$raw_headers = wp_remote_retrieve_headers( $response );

		$headers = [
			'ratelimit-remaining' => (int) $raw_headers['x-ratelimit-remaining'],
		];
		if ( isset( $raw_headers['x-total'] ) && isset( $raw_headers['x-per-page'] ) ) {
			$headers['total']       = (int) $raw_headers['x-total'];
			$headers['total_pages'] = (int) ceil( $raw_headers['x-total'] / $raw_headers['x-per-page'] );
		}

		$response = [
			'body'    => $body,
			'headers' => $headers,
		];

		$cache->set_cache( $response );
		$response['cached'] = false;
		return $response;
	}

	/**
	 * Helper method to submit remote url requests.
	 *
	 * @param string $url URL to requested.
	 * @param array  $args Optional. Set other arguments to be passed to wp_remote_get().
	 *
	 * @return array|WP_Error
	 */
	public function get_remote( $url, $args = [] ) {
		if ( $this->plugin->is_wpcom_vip_prod() && function_exists( 'vip_safe_wp_remote_get' ) ) {
			$response = vip_safe_wp_remote_get( $url, '', 3, 3, 20, $args );
		} else {
			$response = wp_remote_get( $url, $args ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		}

		return $response;
	}

	/**
	 * Format exception into usable WP_Error objects.
	 *
	 * @param string|int $code Error code.
	 * @param int        $error_status HTTP error state code. Default to 500.
	 *
	 * @return WP_Error
	 */
	public function format_exception( $code, $error_status = 500 ) {
		if ( is_numeric( $error_status ) ) {
			switch ( $error_status ) {
				case 400:
					/* translators: %s: Link to status page. */
					$message = sprintf( __( 'There appears to be a communication issue with Unsplash, please check <a href="%s">status.unsplash.com</a> and try again in a few minutes.', 'unsplash' ), 'https://status.unsplash.com' );
					break;
				case 401:
					/* translators: %s: Link to settings page. */
					$message = sprintf( __( 'The Unsplash API credentials supplied are not authorized. Please visit the <a href="%s">Unsplash settings page</a> to reconnect to Unsplash now.', 'unsplash' ), get_admin_url( null, 'options-general.php?page=unsplash' ) );
					break;
				case 403:
					/* translators: %s: Link to settings page. */
					$message = sprintf( __( 'The Unsplash API credentials supplied are not authorized for this request. Please visit the <a href="%s">Unsplash settings page</a> to reconnect to Unsplash now.', 'unsplash' ), get_admin_url( null, 'options-general.php?page=unsplash' ) );
					break;
				case 404:
					$message = __( 'Unable to find Unsplash resource.', 'unsplash' );
					break;
				case 429:
					$message = __( 'The Unsplash API credentials supplied have been flagged for exceeding the permitted rate limit and have been temporarily disabled.', 'unsplash' );
					break;
				case 400:
				case 500:
					/* translators: %s: Link to status page. */
					$message = sprintf( __( 'There appears to be a communication issue with Unsplash, please check <a href="%s">status.unsplash.com</a> and try again in a few minutes.', 'unsplash' ), 'https://status.unsplash.com' );
					break;
				default:
					$message = get_status_header_desc( $error_status );
					if ( empty( $message ) ) {
						return $this->format_exception( $code );
					}
					break;
			}
		} else {
			return $this->format_exception( $code );
		}

		return new WP_Error( $code, $message, [ 'status' => $error_status ] );
	}
}

