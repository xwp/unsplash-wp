<?php
/**
 * Cache for api requests.
 *
 * @package Unsplash
 */

namespace Unsplash;

use WP_REST_Request;

/**
 * Cache for api requests.
 */
class Api_Cache {

	/**
	 * WP_REST_Request instance.
	 *
	 * @var WP_REST_Request
	 */
	public $request;

	/**
	 * Is it cached.
	 *
	 * @var bool
	 */
	protected $is_cached = false;
	/**
	 * Cache key.
	 *
	 * @var string
	 */
	protected $key = '';

	/**
	 * __construct method.
	 *
	 * @param WP_REST_Request $request WP_REST_Request Object.
	 */
	public function __construct( WP_REST_Request $request ) {
		$this->request = $request;
		$this->generate_key();
	}

	/**
	 * Generate cache key.
	 *
	 * @return bool Returns true.
	 */
	public function generate_key() {
		$params          = (array) $this->request->get_params();
		$params['route'] = (string) $this->request->get_route();
		$params_encoded  = wp_json_encode( $params );
		$this->key       = 'unsplash_cache_' . md5( $params_encoded );

		return true;
	}

	/**
	 * If the transient does not exist, does not have a value, or has expired, then the return value will be false.
	 *
	 * @return mixed Value of transient.
	 */
	public function get_cache() {
		/*
		 * TODO: Remove once API solution is in place.
		 *
		 * This is intentional. It will be removed once we have our own API solution in place,
		 * which will internally do caching.
		 */
		$transient       = false;
		$this->is_cached = ! empty( $transient );

		return $transient;
	}

	/**
	 * Set cache value in transient.
	 *
	 * @param mixed $value Transient value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
	 * @return False if value was not set and true if value was set.
	 */
	public function set_cache( $value ) {
		return set_transient( $this->key, $value, HOUR_IN_SECONDS );
	}

	/**
	 * Return value of is_cached,
	 *
	 * @return int Return value of is_cached,
	 */
	public function get_is_cached() {
		return (int) $this->is_cached;
	}

}
