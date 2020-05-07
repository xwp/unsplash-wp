<?php
/**
 * API Response class.
 *
 * @package Unsplash
 */

namespace Unsplash;

/**
 * Class Api_Response.
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
	 * Api_Response constructor.
	 *
	 * @param array $results Array of results for API.
	 * @param int   $total_pages Total number of pages.
	 * @param int   $total_objects Total number of objects.
	 * @param bool  $cached Is cached.
	 */
	public function __construct( $results, $total_pages = 0, $total_objects = 0, $cached = false ) {
		// @todo handle bad/missing `$results` and add test coverage.
		$this->results       = $results;
		$this->total_pages   = $total_pages;
		$this->total_objects = $total_objects;
		$this->cached        = $cached;
	}

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
