<?php
/**
 * API Response class.
 *
 * @package Unsplash
 */

namespace Unsplash;

/**
 * Class Api_Response.
 *
 * @package Unsplash
 */
class Api_Response {
	/**
	 * Is cached.
	 *
	 * @var bool
	 */
	protected $cached = false;
	/**
	 * Total pages.
	 *
	 * @var int
	 */
	protected $total_pages = 0;
	/**
	 * Total objects.
	 *
	 * @var int
	 */
	protected $total_objects = 0;
	/**
	 * Results.
	 *
	 * @var array
	 */
	protected $results = [];

	/**
	 * Get cached value.
	 *
	 * @return mixed
	 */
	public function get_cached() {
		return $this->cached;
	}

	/**
	 * Get total objects.
	 *
	 * @return int
	 */
	public function get_total_object() {
		return $this->total_objects;
	}

	/**
	 * Get total pages.
	 *
	 * @return int
	 */
	public function get_total_pages() {
		return $this->total_pages;
	}

	/**
	 * Get results.
	 *
	 * @return array
	 */
	public function get_results() {
		return $this->results;
	}
}
