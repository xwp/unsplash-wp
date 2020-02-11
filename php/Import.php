<?php
/**
 * Import image class.
 *
 * @package XWP\Unsplash\Import.
 */

namespace XWP\Unsplash;

use WP_Error;

/**
 * Class Import
 *
 * @package XWP\Unsplash
 */
class Import {

	/**
	 * Unsplash ID.
	 *
	 * @var string
	 */
	protected $id = 0;
	/**
	 * Unsplash image object.
	 *
	 * @var Image
	 */
	protected $image;
	/**
	 * URL for download.
	 *
	 * @var string
	 */
	protected $link = '';

	/**
	 * Post ID.
	 *
	 * @var int
	 */
	protected $parent = 0;
	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	protected $attachment_id = 0;

	/**
	 * Processed fields.
	 *
	 * @var array
	 */
	protected $process_data = [];
	/**
	 * Hardcoded file ext.
	 */
	const EXT = 'jpeg';

	/**
	 * Hardcoded MINE type.
	 */
	const MIME = 'image/jpeg';

	/**
	 * Import constructor.
	 *
	 * @param string $id Unsplash ID.
	 * @param Image  $image Unsplash image object.
	 * @param string $link URL of download image.
	 * @param int    $parent Parent ID.
	 */
	public function __construct( $id, $image = null, $link = '', $parent = 0 ) {
		$this->id     = $id;
		$this->image  = $image;
		$this->link   = $link;
		$this->parent = $parent;
	}

	/**
	 * Process all methods in the correct order.
	 *
	 * @return false|int|WP_Error
	 */
	public function process() {
		$existing_attachment = $this->get_attachment_id();
		if ( $existing_attachment ) {
			return $existing_attachment;
		}
		$file       = $this->import_image();
		$attachment = $this->create_attachment( $file );
		if ( is_wp_error( $attachment ) ) {
			return $attachment;
		}
		$this->process_meta();
		$this->process_tags();
		$this->process_source();
		$this->process_user();

		return $this->attachment_id;
	}

	/**
	 * Get the ID for the attachment.
	 *
	 * @return false|int
	 */
	public function get_attachment_id() {
		$check = get_page_by_path( $this->id, ARRAY_A, 'page' );
		if ( is_array( $check ) ) {
			return $check['ID'];
		}

		return false;
	}

	/**
	 * Import image to a temp directory and move it into WP content directory.
	 *
	 * @return array|string|WP_Error
	 */
	public function import_image() {
		if ( ! function_exists( 'download_url' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}

		$file_array = [];
		$file       = $this->link;
		$tmp        = download_url( $file );

		$file_array['name']     = $this->image->get_field( 'file' );
		$file_array['tmp_name'] = $tmp;
		$file_array['type']     = self::MIME;
		$file_array['ext']      = self::EXT;

		// If error storing temporarily, unlink.
		if ( is_wp_error( $tmp ) ) {
			if ( ( defined( '\WPCOM_IS_VIP_ENV' ) && \WPCOM_IS_VIP_ENV ) ) {
				@unlink( $file_array['tmp_name'] ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
			}
			$file_array['tmp_name'] = '';

			return $tmp;
		}
		// Pass off to WP to handle the actual upload.
		$overrides = array(
			'test_form' => false,
			'action'    => 'wp_handle_sideload',
		);

		// Bypasses is_uploaded_file() when running unit tests.
		if ( defined( 'DIR_TESTDATA' ) && DIR_TESTDATA ) {
			$overrides['action'] = 'wp_handle_mock_upload';
		}

		$file = wp_handle_upload( $file_array, $overrides );
		if ( isset( $file['error'] ) ) {
			$file = new WP_Error(
				'rest_upload_unknown_error',
				$file['error'],
				array( 'status' => 500 )
			);
		}

		return $file;
	}

	/**
	 * Create attachment object.
	 *
	 * @param array|WP_Error $file Files array or error.
	 *
	 * @return int|WP_Error
	 */
	public function create_attachment( $file ) {
		if ( is_wp_error( $file ) ) {
			return $file;
		}

		if ( empty( $file ) || ! is_array( $file ) ) {
			return new WP_Error( 'no_file_found', __( 'No file found', 'unsplash' ), [ 'status' => 500 ] );
		}

		$url  = $file['url'];
		$file = $file['file'];

		$attachment = new \stdClass();

		$attachment->post_name      = $this->image->get_field( 'original_id' );
		$attachment->post_content   = $this->image->get_field( 'description' );
		$attachment->post_title     = $this->image->get_field( 'alt' );
		$attachment->post_excerpt   = $this->image->get_field( 'alt' );
		$attachment->post_mime_type = self::MIME;
		$attachment->guid           = $url;

		// do the validation and storage stuff.
		$this->attachment_id = wp_insert_attachment( wp_slash( (array) $attachment ), $file, $this->parent, true );

		if ( is_wp_error( $this->attachment_id ) ) {
			if ( 'db_update_error' === $this->attachment_id->get_error_code() ) {
				$this->attachment_id->add_data( array( 'status' => 500 ) );
			} else {
				$this->attachment_id->add_data( array( 'status' => 400 ) );
			}
		}

		return $this->attachment_id;
	}

	/**
	 * Process all fields store in meta.
	 */
	public function process_meta() {
		$map = [
			'color'                    => 'color',
			'original_id'              => 'original_id',
			'original_url'             => 'original_url',
			'unsplash_location'        => 'unsplash_location',
			'unsplash_sponsor'         => 'unsplash_sponsor',
			'unsplash_exif'            => 'unsplash_exif',
			'_wp_attachment_metadata'  => 'meta',
			'_wp_attachment_image_alt' => 'alt',
		];
		foreach ( $map as $key => $value ) {
			update_post_meta( $this->attachment_id, $key, $this->image->get_field( $value ), true );
		}

		return;
	}

	/**
	 * Add media tags to attachment.
	 *
	 * @return array|false|WP_Error
	 */
	protected function process_tags() {
		return wp_set_post_terms( $this->attachment_id, $this->image->get_field( 'tags' ), 'media_tag' );
	}

	/**
	 * Add source to attachment.
	 *
	 * @return array|false|WP_Error
	 */
	protected function process_source() {
		return wp_set_post_terms( $this->attachment_id, [ 'Unsplash' ], 'media_source' );
	}

	/**
	 * Add unsplash user as a term.
	 *
	 * @return array|bool|WP_Error
	 */
	public function process_user() {
		$unsplash_user = $this->image->get_field( 'user' );
		$user          = get_term_by( 'slug', $unsplash_user['id'], 'unsplash_user' );
		if ( ! $user ) {
			$args = [
				'slug'        => $unsplash_user['id'],
				'description' => $unsplash_user['bio'],
			];
			$term = wp_insert_term( $unsplash_user['name'], 'unsplash_user', $args );
			$user = get_term( $term['term_id'], 'unsplash_user' );
			if ( $user && ! is_wp_error( $user ) ) {
				add_term_meta( $term['term_id'], 'unsplash_meta', $unsplash_user );
			}
		}
		if ( $user && ! is_wp_error( $user ) ) {
			return wp_set_object_terms( $this->attachment_id, [ $user->term_id ], 'unsplash_user' );
		}

		return false;
	}
}
