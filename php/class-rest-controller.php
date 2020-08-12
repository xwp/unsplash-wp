<?php
/**
 * REST API Controller class.
 *
 * @package Unsplash.
 */

namespace Unsplash;

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
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
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
			'/' . $this->rest_base . '/(?P<id>[\w-]+)',
			[
				'args'   => [
					'id' => [
						'description' => esc_html__( 'Unsplash image ID.', 'unsplash' ),
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
					'id'          => [
						'description' => esc_html__( 'Unsplash image ID.', 'unsplash' ),
						'type'        => 'string',
					],
					'parent'      => [
						'description' => esc_html__( 'Parent post ID.', 'unsplash' ),
						'type'        => 'integer',
						'default'     => 0,
					],
					'alt'         => [
						'description' => esc_html__( 'Image alt text.', 'unsplash' ),
						'type'        => 'string',
					],
					'title'       => [
						'description' => esc_html__( 'Image title.', 'unsplash' ),
						'type'        => 'string',
					],
					'description' => [
						'description' => esc_html__( 'Image description.', 'unsplash' ),
						'type'        => 'string',
					],
					'caption'     => [
						'description' => esc_html__( 'Image caption.', 'unsplash' ),
						'type'        => 'string',
					],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'get_import' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'context' => $this->get_context_param( [ 'default' => 'view' ] ),
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/post-process/(?P<id>[\d]+)',
			[
				'args'   => [
					'id' => [
						'description'       => esc_html__( 'WordPress attachment ID.', 'unsplash' ),
						'type'              => 'integer',
						'validate_callback' => [ $this, 'validate_get_attachment' ],
					],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'post_process' ],
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
		$page        = $request->get_param( 'page' );
		$per_page    = $request->get_param( 'per_page' );
		$order_by    = $request->get_param( 'order_by' );
		$search      = $request->get_param( 'search' );
		$orientation = $request->get_param( 'orientation' );
		$collections = $request->get_param( 'collections' );
		$photos      = [];
		if ( $search ) {
			$api_response = $this->plugin->api->search( $search, $page, $per_page, $orientation, $collections );
		} else {
			$api_response = $this->plugin->api->all( $page, $per_page, $order_by );
		}

		if ( is_wp_error( $api_response ) ) {
			return $this->rest_ensure_response( $api_response, $request );
		}
		$results   = $api_response->get_results();
		$max_pages = $api_response->get_total_pages();
		$total     = $api_response->get_total_object();
		$cached    = (int) $api_response->get_cached();

		foreach ( $results as $index => $photo ) {
			if ( $this->is_ajax_request( $request ) ) {
				$photo = $this->set_unique_media_id( $photo, $index, $page, $per_page );
			}

			$data     = $this->prepare_item_for_response( $photo, $request );
			$photos[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $photos );
		$response->header( 'X-WP-Total', (int) $total );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );
		$response->header( 'X-WP-Unsplash-Cache-Hit', $cached );

		return $this->rest_ensure_response( $response, $request );
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

		$api_response = $this->plugin->api->get( $id );
		if ( is_wp_error( $api_response ) ) {
			return $this->rest_ensure_response( $api_response, $request );
		}
		$results = $api_response->get_results();
		$cached  = (int) $api_response->get_cached();
		$photos  = $this->prepare_item_for_response( $results, $request );

		$response = rest_ensure_response( $photos );
		$response->header( 'X-WP-Unsplash-Cache-Hit', $cached );

		return $this->rest_ensure_response( $response, $request );
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

		$api_response = $this->plugin->api->get( $id );
		if ( is_wp_error( $api_response ) ) {
			return $api_response;
		}
		$results     = $api_response->get_results();
		$credentials = $this->plugin->settings->get_credentials();
		$utm_source  = $credentials['utmSource'];
		$image       = new Image( $results, $utm_source );
		$image->set_field( 'alt', $request->get_param( 'alt' ) );
		$image->set_field( 'title', $request->get_param( 'title' ) );
		$image->set_field( 'description', $request->get_param( 'description' ) );
		$image->set_field( 'caption', $request->get_param( 'caption' ) );

		$importer      = new Import( $id, $image, $request->get_param( 'parent' ) );
		$attachment_id = $importer->process();
		// @codeCoverageIgnoreStart
		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}
		// @codeCoverageIgnoreEnd
		$this->plugin->api->download( $id );
		$response = $this->prepare_item_for_response( $results, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 301 );
		$response->header( 'X-WP-Upload-Attachment-ID', $attachment_id );
		$response->header( 'Location', add_query_arg( 'context', 'edit', rest_url( sprintf( '%s/%s/%d', 'wp/v2', 'media', $attachment_id ) ) ) );

		return $this->rest_ensure_response( $response, $request );
	}

	/**
	 * Process image.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error Single page of photo results.
	 */
	public function post_process( $request ) {
		$attachment_id = $request->get_param( 'id' );
		$retry         = (bool) $request->get_param( 'retry' );
		try {
			// @codeCoverageIgnoreStart
			if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
			}
			// @codeCoverageIgnoreEnd

			if ( $retry ) {
				// On retry, try to scale back images generated.
				add_filter( 'intermediate_image_sizes_advanced', [ $this, 'intermediate_image_sizes_advanced' ] );
				add_filter( 'big_image_size_threshold', '__return_zero' );
			}
			$meta     = (array) get_post_meta( $attachment_id, 'unsplash_attachment_metadata', true );
			$file     = get_attached_file( $attachment_id );
			$new_meta = wp_generate_attachment_metadata( $attachment_id, $file );
			unset( $meta['sizes'], $new_meta['image_meta'] );
			$new_meta  = wp_parse_args( $new_meta, $meta );
			$processed = wp_update_attachment_metadata( $attachment_id, $new_meta );
			$data      = [
				'processed' => $processed,
				'retry'     => $retry,
			];
			if ( $retry ) {
				// Return filters.
				remove_filter( 'intermediate_image_sizes_advanced', [ $this, 'intermediate_image_sizes_advanced' ] );
				remove_filter( 'big_image_size_threshold', '__return_zero' );
			}
		} catch ( \Exception $e ) {
			$response = new WP_Error(
				'rest_unsplash_single_photo_process',
				/* translators: %d: attachment id */
				sprintf( esc_html__( 'Unable to process image attachment %d.', 'unsplash' ), $attachment_id ),
				[
					'attachment_id' => $attachment_id,
					'status'        => '400',
				]
			);
			$this->plugin->trigger_warning( $e->getMessage() );
			return $response;
		}

		return $this->rest_ensure_response( $data, $request );
	}

	/**
	 * If enable to generate all image sizes, just try and generate core default image sizes.
	 *
	 * @param array $sizes Current image sizes.
	 *
	 * @return array
	 */
	public function intermediate_image_sizes_advanced( $sizes ) {
		return array_intersect_key( $sizes, array_flip( [ 'thumbnail', 'medium', 'medium_large', 'large' ] ) );
	}

	/**
	 * Format response for AJAX.
	 *
	 * @param  mixed           $response Response data.
	 * @param  WP_REST_Request $request  Request.
	 * @return  WP_REST_Response          Repsonse object.
	 */
	public function rest_ensure_response( $response, WP_REST_Request $request ) {
		if ( $this->is_ajax_request( $request ) ) {
			if ( is_wp_error( $response ) ) {
				$wp_error = $response;
				$response = array( 'success' => false );
				$result   = array();
				foreach ( $wp_error->errors as $code => $messages ) {
					$data = ( isset( $wp_error->error_data[ $code ] ) ) ? $wp_error->error_data[ $code ] : [];
					foreach ( $messages as $message ) {
						$result[] = array(
							'code'    => $code,
							'message' => $message,
							'data'    => $data,
						);
					}
				}
				$response['data'] = $result;
			}
		}
		return rest_ensure_response( $response );
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
				esc_html__( 'Sorry, you are not allowed to edit posts in this post type.', 'unsplash' ),
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
				esc_html__( 'Sorry, you are not allowed to create posts as this user.', 'unsplash' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		if ( ! current_user_can( 'upload_files' ) ) {
			return new WP_Error(
				'rest_cannot_create',
				esc_html__( 'Sorry, you are not allowed to upload media on this site.', 'unsplash' ),
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
	 * @return array|WP_Error|WP_REST_Response Array if its an AJAX request,
	 * WP_Error if an error occurs, otherwise a REST response object.
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

		$query_params['context']['default'] = 'view';

		$query_params['per_page']['maximum'] = 30;

		$query_params['order_by'] = [
			'description' => esc_html__( 'How to sort the photos.', 'unsplash' ),
			'type'        => 'string',
			'default'     => 'latest',
			'enum'        => [ 'latest', 'oldest', 'popular' ],
		];

		$query_params['orientation'] = [
			'default'     => null,
			'enum'        => [ 'landscape', 'portrait', 'squarish' ],
			'description' => esc_html__( 'Filter search results by photo orientation.', 'unsplash' ),
			'type'        => 'string',
		];

		$query_params['collections'] = [
			'default'           => null,
			'type'              => 'string',
			'description'       => esc_html__( 'Collection ID(‘s) to narrow search. If multiple, comma-separated.', 'unsplash' ),
			'validate_callback' => [ static::class, 'validate_get_search_param' ],
		];

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
	 * Check if attachment exists.
	 *
	 * @param int $param Attachment ID.
	 * @return bool|WP_Error
	 */
	public function validate_get_attachment( $param ) {
		$attachment = get_post( (int) $param );

		if ( empty( $attachment ) ) {
			return new WP_Error(
				'rest_post_invalid_id',
				esc_html__( 'Invalid attachment ID.', 'unsplash' ),
				array( 'status' => 400 )
			);
		}

		if ( get_post_type( $attachment ) !== $this->post_type ) {
			return new WP_Error(
				'rest_invalid_post_type_id',
				esc_html__( 'Invalid attachment ID.', 'unsplash' ),
				array( 'status' => 400 )
			);
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
		// @todo Add in all required fields.

		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'type',
			'type'       => 'photo',
			'properties' => [
				'id'              => [
					'description' => esc_html__( 'Unique identifier for the object.', 'unsplash' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'created_at'      => [
					'description' => esc_html__( 'The date the object was published.', 'unsplash' ),
					'type'        => [ 'string', 'null' ],
					'format'      => 'date-time',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'updated_at'      => [
					'description' => esc_html__( 'The date the object was last modified.', 'unsplash' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'alt_description' => [
					'description' => esc_html__( 'Alternative text to display when attachment is not displayed.', 'unsplash' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'description'     => [
					'description' => esc_html__( 'Description for the object, as it exists in the database.', 'unsplash' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'color'           => [
					'description' => esc_html__( 'Color for the object, as it exists in the database.', 'unsplash' ),
					'type'        => 'string',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'height'          => [
					'description' => esc_html__( 'Height for the object.', 'unsplash' ),
					'type'        => 'integer',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'width'           => [
					'description' => esc_html__( 'Width for the object.', 'unsplash' ),
					'type'        => 'integer',
					'context'     => [ 'view', 'edit', 'embed' ],
					'readonly'    => true,
				],
				'urls'            => [
					'description' => esc_html__( 'List of url for default image sizes for the object.', 'unsplash' ),
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

	/**
	 * Set a unique ID on the photo so that it does not clash with other media objects in the media library.
	 *
	 * @param array $photo    Photo attributes.
	 * @param int   $index    Index of $photo in current page.
	 * @param int   $page     Current page.
	 * @param int   $per_page Number of photos per page.
	 *
	 * @return array Photo with updated ID.
	 */
	public function set_unique_media_id( $photo, $index, $page, $per_page ) {
		/*
		 * The media selector uses the image ID to sort the list of images received from the API, so an
		 * incremental ID is generated and set on the photo so that they can be ordered correctly.
		 *
		 * The 'unsplash-' prefix is added to prevent any attachment ID collisions in the media selector and
		 * will be stripped when media objects are being compared.
		 */
		$photo['unsplash_order'] = ( $index + ( ( $page - 1 ) * $per_page ) );

		return $photo;
	}
}
