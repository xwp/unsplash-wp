<?php
/**
 * REST API Controller class.
 *
 * @package Unsplash.
 */

namespace Unsplash;

use Crew\Unsplash\HttpClient;
use Crew\Unsplash\Photo;
use Crew\Unsplash\Search;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;
use WP_Error;

/**
 * REST API Controller.
 */
class Rest_Controller extends WP_REST_Controller {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin
	 */
	public $plugin;

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type;

	/**
	 * Constructor.
	 *
	 * @param Plugin $plugin Instance of the plugin abstraction.
	 */
	public function __construct( $plugin ) {
		$this->plugin    = $plugin;
		$this->namespace = 'unsplash/v1';
		$this->rest_base = 'photos';
		$this->post_type = 'attachment';
	}

	/**
	 * Initiate the class.
	 */
	public function init() {
		$this->plugin->add_doc_hooks( $this );

		HttpClient::init(
			[
				'applicationId' => $this->plugin->settings->get_option( 'access_key', 'UNSPLASH_ACCESS_KEY' ),
				'secret'        => $this->plugin->settings->get_option( 'secret_key', 'UNSPLASH_SECRET_KEY' ),
				'utmSource'     => $this->plugin->settings->get_option( 'utm_source', 'UNSPLASH_UTM_SOURCE' ),
			]
		);
	}

	/**
	 * Registers the routes for the Unsplash API.
	 *
	 * @see register_rest_route()
	 *
	 * @action rest_api_init
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $this->get_collection_params(),
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/search',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_search' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $this->get_search_params(),
				],
				'schema' => [ $this, 'get_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\w-]+)',
			[
				'args'   => [
					'id' => [
						'description' => __( 'Unsplash image ID.', 'unsplash' ),
						'type'        => 'string',
					],
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
					'args'                => [
						'context' => $this->get_context_param( [ 'default' => 'view' ] ),
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/import/(?P<id>[\w-]+)',
			[
				'args'   => [
					'id' => [
						'description' => __( 'Unsplash image ID.', 'unsplash' ),
						'type'        => 'string',
					],
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_import' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'context' => $this->get_context_param( [ 'default' => 'view' ] ),
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
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
		$photos   = [];

		try {
			$api_response = Photo::all( $page, $per_page, $order_by );
			$results      = $api_response->toArray();
			$max_pages    = $api_response->totalPages();
			$total        = $api_response->totalObjects();

			foreach ( $results as $index => $photo ) {
				$photo['unsplash_id'] = $photo['id'];

				/*
				 * Set an incremental ID so that the media selector can order the images correctly.
				 * The 'unsplash-' prefix is added to prevent any attachment ID collisions in the media library and
				 * will be stripped when the image models are being compared.
				 */
				$photo['id'] = 'unsplash-' . ( $index + ( ( $page - 1 ) * $per_page ) );

				$data     = $this->prepare_item_for_response( $photo, $request );
				$photos[] = $this->prepare_response_for_collection( $data );
			}

			$response = rest_ensure_response( $photos );
			$response->header( 'X-WP-Total', (int) $total );
			$response->header( 'X-WP-TotalPages', (int) $max_pages );
		} catch ( \Exception $e ) {
			$response = new WP_Error( 'all-photos', __( 'An unknown error occurred while retrieving the photos', 'unsplash' ), [ 'status' => '500' ] );
			$this->plugin->log_error( $e );
		}

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
		$id = $request->get_param( 'id' );

		try {
			$results = Photo::find( $id )->toArray();
			$photos  = $this->prepare_item_for_response( $results, $request );
		} catch ( \Exception $e ) {
			$photos = new WP_Error( 'single-photo', __( 'An unknown error occurred while retrieving the photo', 'unsplash' ), [ 'status' => '500' ] );
			$this->plugin->log_error( $e );
		}

		return rest_ensure_response( $photos );
	}

	/**
	 * Import image into WP.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error Single page of photo results.
	 */
	public function get_import( $request ) {
		$id = $request->get_param( 'id' );

		try {
			$photo = Photo::find( $id );
			$photo->download();
			$results = $photo->toArray();

			$image         = new Image( $this->plugin, $results );
			$importer      = new Import( $id, $image );
			$attachment_id = $importer->process();
			if ( is_wp_error( $attachment_id ) ) {
				return $attachment_id;
			}

			$response = $this->prepare_item_for_response( $results, $request );
			$response = rest_ensure_response( $response );
			$response->set_status( 301 );
			$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', 'wp/v2', 'media', $attachment_id ) ) );
		} catch ( \Exception $e ) {
			$response = new WP_Error( 'single-photo-download', __( 'An unknown error occurred while retrieving the photo', 'unsplash' ), [ 'status' => '500' ] );
			$this->plugin->log_error( $e );
		}

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
		$photos      = [];

		try {
			$api_response = Search::photos( $search, $page, $per_page, $orientation, $collections )->getArrayObject();
			$results      = $api_response->toArray();
			$max_pages    = $api_response->totalPages();
			$total        = $api_response->totalObjects();

			foreach ( $results as $photo ) {
				$data     = $this->prepare_item_for_response( $photo, $request );
				$photos[] = $this->prepare_response_for_collection( $data );
			}

			$response = rest_ensure_response( $photos );
			$response->header( 'X-WP-Total', (int) $total );
			$response->header( 'X-WP-TotalPages', (int) $max_pages );
		} catch ( \Exception $e ) {
			$response = new WP_Error( 'search-photos', __( 'An unknown error occurred while searching for a photo', 'unsplash' ), [ 'status' => '500' ] );
			$this->plugin->log_error( $e );
		}

		return $response;
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @see   https://github.com/WordPress/WordPress/blob/c85c8f5235356cbf65680a3201e9ee4161803c0b/wp-includes/rest-api/endpoints/class-wp-rest-posts-controller.php#L128-L149
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		$post_type = get_post_type_object( $this->post_type );

		if ( 'edit' === $request['context'] && ! current_user_can( $post_type->cap->edit_posts ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit posts in this post type.', 'unsplash' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return current_user_can( 'edit_posts' );
	}

	/**
	 * Checks if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return bool|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Checks if a given request has access to create items.
	 *
	 * @see   https://github.com/WordPress/WordPress/blob/c85c8f5235356cbf65680a3201e9ee4161803c0b/wp-includes/rest-api/endpoints/class-wp-rest-posts-controller.php#L515-L567
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {

		$post_type = get_post_type_object( $this->post_type );

		if ( ! current_user_can( $post_type->cap->create_posts ) ) {
			return new WP_Error(
				'rest_cannot_create',
				__( 'Sorry, you are not allowed to create posts as this user.', 'unsplash' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error(
				'rest_cannot_create',
				__( 'Sorry, you are not allowed to upload media on this site.', 'unsplash' ),
				[ 'status' => 400 ]
			);
		}

		return true;
	}

	/**
	 * Prepares a single photo output for response.
	 *
	 * @param array|Object    $photo Photo object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array|WP_Error|WP_REST_Response Array if its an AJAX request, WP_Error if an error occurs, otherwise a REST response object.
	 */
	public function prepare_item_for_response( $photo, $request ) {
		if ( $this->is_ajax_request( $request ) ) {
			return $this->plugin->wp_prepare_attachment_for_js( $photo );
		}

		$fields     = $this->get_fields_for_response( $request );
		$schema     = $this->get_item_schema();
		$properties = $schema['properties'];
		$data       = [];

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
		return rest_ensure_response( $data );
	}

	/**
	 * Override default collection params to add Unsplash fields.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();
		unset( $query_params['search'] );

		$query_params['context']['default'] = 'view';

		$query_params['per_page']['maximum'] = 30;

		$query_params['order_by'] = [
			'description' => __( 'How to sort the photos.', 'unsplash' ),
			'type'        => 'string',
			'default'     => 'latest',
			'enum'        => [ 'latest', 'oldest', 'popular' ],
		];

		return $query_params;
	}

	/**
	 * Override default collection for search  params to add Unsplash fields.
	 *
	 * @return array
	 */
	public function get_search_params() {
		$query_params = parent::get_collection_params();

		$query_params['context']['default'] = 'view';

		$query_params['search'] = [
			'description' => __( 'Limit results to those matching a string.', 'unsplash' ),
			'type'        => 'string',
			'required'    => true,
		];

		$query_params['orientation'] = [
			'default'     => null,
			'enum'        => [ 'landscape', 'portrait', 'squarish' ],
			'description' => __( 'Filter search results by photo orientation.', 'unsplash' ),
			'type'        => 'string',
		];

		$query_params['collections'] = [
			'default'           => null,
			'type'              => 'string',
			'description'       => __( 'Collection ID(‘s) to narrow search. If multiple, comma-separated.', 'unsplash' ),
			'validate_callback' => [ static::class, 'validate_get_search_param' ],
		];

		$query_params['per_page']['maximum'] = 30;

		return $query_params;
	}

	/**
	 * Validate parameters for get_search().
	 *
	 * @param string          $value   Parameter value.
	 * @param WP_REST_Request $request Request.
	 * @param string          $param   Parameter name.
	 * @return bool True if valid, false if not.
	 */
	public static function validate_get_search_param( $value, $request, $param ) {
		if ( 'collections' === $param ) {
			// Only digits are accepted. If there are multiple IDs, they must be comma delimited.
			return (bool) preg_match( '/^(\d+(,\d+)*)$/', $value );
		}

		return true;
	}

	/**
	 * Retrieves the photo type's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		if ( property_exists( self::class, 'schema' ) && null !== $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}
		// TODO Add in all required fields.

		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'type',
			'type'       => 'photo',
			'properties' => [
				'id'              => [
					'description' => __( 'Unique identifier for the object.', 'unsplash' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'created_at'      => [
					'description' => __( 'The date the object was published.', 'unsplash' ),
					'type'        => [ 'string', 'null' ],
					'format'      => 'date-time',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'updated_at'      => [
					'description' => __( 'The date the object was last modified.', 'unsplash' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'alt_description' => [
					'description' => __( 'Alternative text to display when attachment is not displayed.', 'unsplash' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'description'     => [
					'description' => __( 'Description for the object, as it exists in the database.', 'unsplash' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'color'           => [
					'description' => __( 'Color for the object, as it exists in the database.', 'unsplash' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'height'          => [
					'description' => __( 'Height for the object.', 'unsplash' ),
					'type'        => 'integer',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'width'           => [
					'description' => __( 'Width for the object.', 'unsplash' ),
					'type'        => 'integer',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'urls'            => [
					'description' => __( 'List of url for default image sizes for the object.', 'unsplash' ),
					'type'        => 'object',
					'properties'  => [],
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
			],
		];

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Determine if a request is an AJAX one.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool
	 */
	public function is_ajax_request( $request ) {
		return 'XMLHttpRequest' === $request->get_header( 'X-Requested-With' );
	}
}
