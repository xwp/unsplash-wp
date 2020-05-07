<?php
/**
 * Cache for api requests.
 *
 * @package Unsplash
 */

namespace Unsplash;

/**
 * Cache for api requests.
 */
class Api_Cache {

	/**
	 * Args passed to url of cached response.
	 *
	 * @var array
	 */
	protected $query_args = [];

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
	 * Api_Cache constructor.
	 *
	 * @param array $query_args Args passed to url of cached response.
	 */
	public function __construct( array $query_args = [] ) {
		$this->query_args = $query_args;
		$this->generate_key();
	}

	/**
	 * Generate cache key.
	 *
	 * @return bool Returns true.
	 */
	public function generate_key() {
		$params_encoded = wp_json_encode( $this->query_args );
		$this->key      = 'unsplash_cache_v1_' . md5( $params_encoded );

		return true;
	}

	/**
	 * If the transient does not exist, does not have a value, or has expired, then the return value will be false.
	 *
	 * @return mixed Value of transient.
	 */
	public function get_cache() {
		$transient       = get_transient( $this->key );
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
		return set_transient( $this->key, $value, MINUTE_IN_SECONDS * 15 );
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
