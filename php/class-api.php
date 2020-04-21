<?php
namespace Unsplash;


use WP_Error;
/**
 * Class API
 *
 * @package Unsplash
 */
class API {
	/**
	 * @var array
	 */
	protected $credical = [];

	/**
	 * API constructor.
	 *
	 * @param array $credical
	 */
	public function __construct( array $credical ) {
		$this->credical = $credical;
	}

	/**
	 * @param $id
	 *
	 * @return Api_Response
	 */
	public function get( $id ) {
		return $this->send_request( '', [] );
	}

	/**
	 * @param $id
	 *
	 * @return Api_Response|WP_Error
	 */
	public function download( $id ) {
		return $this->send_request( '', [] );
	}

	/**
	 * @param $page
	 * @param $per_page
	 * @param $order_by
	 *
	 * @return Api_Response|WP_Error
	 */
	public function all( $page, $per_page, $order_by ) {
		return $this->send_request( '', [] );
	}

	/**
	 * @param $search
	 * @param $page
	 * @param $per_page
	 * @param $orientation
	 * @param $collections
	 *
	 * @return Api_Response|WP_Error
	 */
	public function search( $search, $page, $per_page, $orientation, $collections ) {
		return $this->send_request( '', [] );
	}

	/**
	 * @param $path
	 * @param $args
	 *
	 * @return Api_Response|WP_Error
	 */
	public function send_request( $path, $args ) {
		$cache = new Api_cache();

		if ( 1 == 2 ){
			return new WP_Error();
		}

		return new Api_Response();
	}
}

