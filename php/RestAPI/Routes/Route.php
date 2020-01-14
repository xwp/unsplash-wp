<?php
/**
 * Route class.
 *
 * @package XWP\Unsplash\RestAPI\Routes
 */

namespace XWP\Unsplash\RestAPI\Routes;

use WP_REST_Server;

/**
 * Generic route class.
 *
 * Includes the method `get_options` to generate an array of options for `register_rest_route` to consume.
 */
abstract class Route {

	/**
	 * Array of possible actions for route.
	 *
	 * @var array
	 */
	private static $actions = [
		'read'   => WP_REST_Server::READABLE,
		'create' => WP_REST_Server::CREATABLE,
		'edit'   => WP_REST_Server::EDITABLE,
		'delete' => WP_REST_Server::DELETABLE,
	];

	/**
	 * Generates a customized array of options for the route class.
	 *
	 * @return array Array of options for route.
	 */
	public static function get_options() {
		$options = [];

		foreach ( self::$actions as $action => $method ) {
			if ( method_exists( static::class, $action ) ) {
				$has_args = method_exists( static::class, "${action}_args" );

				$options[] = [
					'methods'  => $method,
					'callback' => [ static::class, $action ],
					'args'     => $has_args ? call_user_func( [ static::class, "${action}_args" ] ) : [],
				];
			}
		}

		return $options;
	}
}
