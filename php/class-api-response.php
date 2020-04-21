<?php

namespace Unsplash;

class Api_Response {
	protected $cached;
	protected $total_pages   = 0;
	protected $total_objects = 0;
	protected $results       = [];

	/**
	 * @return mixed
	 */
	public function getCached() {
		return $this->cached;
	}

	/**
	 * @param mixed $cached
	 */
	public function setCached( $cached ) {
		$this->cached = $cached;
	}

	/**
	 * @return int
	 */
	public function getTotalPages() {
		return $this->total_pages;
	}

	/**
	 * @param int $total_pages
	 */
	public function setTotalPages( $total_pages ) {
		$this->total_pages = $total_pages;
	}

	/**
	 * @return int
	 */
	public function getTotalObjects() {
		return $this->total_objects;
	}

	/**
	 * @param int $total_objects
	 */
	public function setTotalObjects( $total_objects ) {
		$this->total_objects = $total_objects;
	}

	/**
	 * @return array
	 */
	public function getResults() {
		return $this->results;
	}

	/**
	 * @param array $results
	 */
	public function setResults( $results ) {
		$this->results = $results;
	}


}
