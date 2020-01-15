<?php
/**
 * REST API Controller class.
 *
 * @package XWP\Unsplash\RestAPI.
 */

namespace XWP\Unsplash\RestAPI;

use Crew\Unsplash\HttpClient;
use Crew\Unsplash\Photo;
use Crew\Unsplash\Search;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Response;
use WP_Error;

/**
 * REST API Controller.
 */
class RestController extends WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'unsplash/v1';
		$this->rest_base = 'photos';
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
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\w-]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the object.' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/search/(?P<search>[\w-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_search' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_search_params(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve a page of photos.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response Single page of photo results.
	 */
	public function get_items( $request ) {
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );
		$order_by = $request->get_param( 'order_by' );
		$photos   = array();
		try {
			$results = Photo::all( $page, $per_page, $order_by )->toArray();
			foreach ( $results as $photo ) {
				$data     = $this->prepare_item_for_response( $photo, $request );
				$photos[] = $this->prepare_response_for_collection( $data );
			}
		} catch ( \Exception $e ) {
			$photos = new WP_Error( 'all-photos', __( 'An unknown error occurred while searching for a photo', 'unsplash' ), [ 'status' => '500' ] );

			/**
			 * Stop IDE from complaining.
			 *
			 * @noinspection ForgottenDebugOutputInspection
			 */
			error_log( $e->getMessage(), $e->getCode() );
		}

		$response = rest_ensure_response( $photos );

		return $response;
	}

	/**
	 * Retrieve a page of photo.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response Single page of photo results.
	 */
	public function get_item( $request ) {
		$id     = $request->get_param( 'id' );
		try {
			$results = Photo::find( $id )->toArray();
			$photos  = $this->prepare_item_for_response( $results, $request );
		} catch ( \Exception $e ) {
			$photos = new WP_Error( 'single-photos', __( 'An unknown error occurred while searching for a photo', 'unsplash' ), [ 'status' => '500' ] );

			/**
			 * Stop IDE from complaining.
			 *
			 * @noinspection ForgottenDebugOutputInspection
			 */
			error_log( $e->getMessage(), $e->getCode() );
		}

		$response = rest_ensure_response( $photos );

		return $response;
	}

	/**
	 * Retrieve a page of photos filtered by a search term.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error Single page of photo results.
	 */
	public function get_search( $request ) {
		$search      = $request->get_param( 'search' );
		$page        = $request->get_param( 'page' );
		$per_page    = $request->get_param( 'per_page' );
		$orientation = $request->get_param( 'orientation' );
		$collections = $request->get_param( 'collections' );
		$photos      = array();
		try {
			$results = Search::photos( $search, $page, $per_page, $orientation, $collections )->getArrayObject()->toArray();
			foreach ( $results as $photo ) {
				$data     = $this->prepare_item_for_response( $photo, $request );
				$photos[] = $this->prepare_response_for_collection( $data );
			}
		} catch ( \Exception $e ) {
			$photos = new WP_Error( 'search-photos', __( 'An unknown error occurred while searching for a photo', 'unsplash' ), [ 'status' => '500' ] );

			/**
			 * Stop IDE from complaining.
			 *
			 * @noinspection ForgottenDebugOutputInspection
			 */
			error_log( $e->getMessage(), $e->getCode() );
		}

		$response = rest_ensure_response( $photos );

		return $response;
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		// TODO Change permsissions to edit_posts.
		return true;
	}

	/**
	 * Prepares a single photo output for response.
	 *
	 * @param Object          $photo Photo object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $photo, $request ) {
		$fields     = $this->get_fields_for_response( $request );
		$schema     = $this->get_item_schema();
		$properties = $schema['properties'];
		$data       = array();
		foreach ( $photo as $field => $value ) {
			if ( in_array( $field, $fields, true ) ) {
				$value = rest_sanitize_value_from_schema( $photo[ $field ], $properties[ $field ] );
				if ( is_wp_error( $value ) ) {
					return $value;
				}
				$data[ $field ] = $value;
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );
		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Override default collection params to add unplash fields.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();
		unset( $query_params['search'] );
		$query_params['per_page']['maximum'] = 30;
		$query_params['order_by']            = [
			'default'           => 'latest',
			'enum'              => [ 'latest', 'oldest', 'popular' ],
		];

		return $query_params;
	}

	/**
	 * Override default collection for search  params to add unplash fields.
	 *
	 * @return array
	 */
	public function get_search_params() {
		$query_params = parent::get_collection_params();

		$query_params['orientation'] = [
			'default'           => null,
			'enum'              => [ 'landscape', 'portrait', 'squarish' ],

		];
		$query_params['collections'] = [
			'default'           => null,
			'validate_callback' => static function ( $param ) {
				return ! empty( $param );
			},
		];

		$query_params['per_page']['maximum'] = 30;

		return $query_params;
	}

	/**
	 * Retrieves the photo type's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}
		// TODO Add in all required fields.

		$schema       = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'type',
			'type'       => 'photo',
			'properties' => array(
				'id'              => array(
					'description' => __( 'Unique identifier for the object.', 'unsplash' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'created_at'      => array(
					'description' => __( "The date the object was published, in the site's timezone.", 'unsplash' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'updated_at'      => array(
					'description' => __( "The date the object was last modified.", 'unsplash' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'alt_description' => array(
					'description' => __( 'Alternative text to display when attachment is not displayed.', 'unsplash' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'description'     => array(
					'description' => __( 'Description for the object, as it exists in the database.' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'color'           => array(
					'description' => __( 'Color for the object, as it exists in the database.' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'height'          => array(
					'description' => __( 'Height for the object.' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'width'           => array(
					'description' => __( 'Width for the object.' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'urls'            => array(
					'description' => __( 'List of url for default image sizes for the object.' ),
					'type'        => 'object',
					'properties'  => array(),
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
			),
		);
		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}
}
