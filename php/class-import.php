<?php
/**
 * Import image class.
 *
 * @package Unsplash.
 */

namespace Unsplash;

use WP_Error;
use WP_Query;

/**
 * Class Import
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
	 * Import constructor.
	 *
	 * @param string $id Unsplash ID.
	 * @param Image  $image Unsplash image object.
	 * @param int    $parent Parent ID.
	 */
	public function __construct( $id, $image = null, $parent = 0 ) {
		$this->id     = strtolower( $id );
		$this->image  = $image;
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
		// @codeCoverageIgnoreStart
		if ( is_wp_error( $attachment ) ) {
			return $attachment;
		}
		// @codeCoverageIgnoreEnd

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
		if ( 0 !== $this->attachment_id ) {
			return $this->attachment_id;
		}

		$parsed_args = [
			'post_type'              => 'attachment',
			'name'                   => $this->id,
			'fields'                 => 'ids',
			'order'                  => 'DESC',
			'suppress_filters'       => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'lazy_load_term_meta'    => false,
			'no_found_rows'          => true,
			'posts_per_page'         => 1,
		];

		$get_posts   = new WP_Query();
		$attachments = $get_posts->query( $parsed_args );

		return ! empty( $attachments ) ? array_shift( $attachments ) : false;
	}

	/**
	 * Import image to a temp directory and move it into WP content directory.
	 *
	 * @return array|string|WP_Error
	 */
	public function import_image() {
		// @codeCoverageIgnoreStart
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		// @codeCoverageIgnoreEnd

		$file_array = [];
		$file       = $this->image->get_image_url( 'full' );
		$tmp        = download_url( $file );
		// If there was an error downloading, return the error.

		// @codeCoverageIgnoreStart
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}
		// @codeCoverageIgnoreEnd

		$file_array['name']     = $this->image->get_field( 'file' );
		$file_array['tmp_name'] = $tmp;
		$file_array['type']     = $this->image->get_field( 'mime_type' );
		$file_array['ext']      = $this->image->get_field( 'ext' );

		// Pass off to WP to handle the actual upload.
		$overrides = [
			'test_form' => false,
			'action'    => 'wp_handle_sideload',
		];

		// Bypasses is_uploaded_file() when running unit tests.
		if ( defined( 'DIR_TESTDATA' ) && DIR_TESTDATA ) {
			$overrides['action'] = 'wp_handle_mock_upload';
		}

		// See https://github.com/WordPress/WordPress/blob/12709269c19d435de019b54d2bda7e4bd1ad664e/wp-includes/rest-api/endpoints/class-wp-rest-attachments-controller.php#L747-L750 .
		$size_check = $this->check_upload_size( $file_array );
		// @codeCoverageIgnoreStart
		if ( is_wp_error( $size_check ) ) {
			return $size_check;
		}
		// @codeCoverageIgnoreEnd

		$file = wp_handle_upload( $file_array, $overrides );
		// @codeCoverageIgnoreStart
		if ( isset( $file['error'] ) ) {
			$file = new WP_Error(
				'rest_upload_unknown_error',
				$file['error'],
				[ 'status' => 500 ]
			);
		}
		// @codeCoverageIgnoreEnd

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
		// @codeCoverageIgnoreStart
		if ( is_wp_error( $file ) ) {
			return $file;
		}

		if ( empty( $file ) || ! is_array( $file ) ) {
			return new WP_Error( 'no_file_found', esc_html__( 'No file found', 'unsplash' ), [ 'status' => 500 ] );
		}
		// @codeCoverageIgnoreEnd

		$url  = $file['url'];
		$file = $file['file'];

		$attachment = [
			'post_name'      => $this->image->get_field( 'original_id' ),
			'post_content'   => $this->image->get_field( 'description' ),
			'post_title'     => $this->image->get_field( 'title' ),
			'post_excerpt'   => $this->image->get_field( 'caption' ),
			'post_mime_type' => $this->image->get_field( 'mime_type' ),
			'guid'           => $url,
		];


		// do the validation and storage stuff.
		$this->attachment_id = wp_insert_attachment( wp_slash( $attachment ), $file, $this->parent, true );
		// @codeCoverageIgnoreStart
		if ( is_wp_error( $this->attachment_id ) ) {
			if ( 'db_update_error' === $this->attachment_id->get_error_code() ) {
				$this->attachment_id->add_data( [ 'status' => 500 ] );
			} else {
				$this->attachment_id->add_data( [ 'status' => 400 ] );
			}
		}
		// @codeCoverageIgnoreEnd

		return $this->attachment_id;
	}

	/**
	 * Process all fields store in meta.
	 */
	public function process_meta() {
		$map = [
			'color'                        => 'color',
			'original_id'                  => 'original_id',
			'original_url'                 => 'original_url',
			'original_link'                => 'original_link',
			'unsplash_location'            => 'unsplash_location',
			'unsplash_sponsor'             => 'unsplash_sponsor',
			'unsplash_exif'                => 'unsplash_exif',
			'_wp_attachment_metadata'      => 'meta',
			'unsplash_attachment_metadata' => 'meta',
			'_wp_attachment_image_alt'     => 'alt',
		];
		foreach ( $map as $key => $value ) {
			update_post_meta( $this->attachment_id, $key, $this->image->get_field( $value ), true );
		}
	}

	/**
	 * Add media tags to attachment.
	 *
	 * @return array|false|WP_Error
	 */
	public function process_tags() {
		return wp_set_post_terms( $this->attachment_id, $this->image->get_field( 'tags' ), 'media_tag' );
	}

	/**
	 * Add source to attachment.
	 *
	 * @return array|false|WP_Error
	 */
	public function process_source() {
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
			if ( ! is_array( $term ) ) {
				return false;
			}
			$user = get_term( $term['term_id'], 'unsplash_user' );
			if ( $user && ! is_wp_error( $user ) ) {
				add_term_meta( $term['term_id'], 'unsplash_meta', $unsplash_user );
			}
		}

		if ( $user && ! is_wp_error( $user ) ) {
			return wp_set_post_terms( $this->attachment_id, [ $user->term_id ], 'unsplash_user' );
		}

		return false;
	}

	/**
	 * Determine if uploaded file exceeds space quota on multisite.
	 *
	 * Replicates check_upload_size().
	 *
	 * @codeCoverageIgnore
	 *
	 * @see https://github.com/WordPress/WordPress/blob/12709269c19d435de019b54d2bda7e4bd1ad664e/wp-includes/rest-api/endpoints/class-wp-rest-attachments-controller.php#L959-L1012
	 *
	 * @param array $file $_FILES array for a given file.
	 * @return true|WP_Error True if can upload, error for errors.
	 */
	protected function check_upload_size( $file ) {
		if ( ! is_multisite() ) {
			return true;
		}

		if ( get_site_option( 'upload_space_check_disabled' ) ) {
			return true;
		}

		$space_left = get_upload_space_available();

		$file_size = filesize( $file['tmp_name'] );

		if ( $space_left < $file_size ) {
			return new WP_Error(
				'rest_upload_limited_space',
				/* translators: %s: Required disk space in kilobytes. */
				sprintf( esc_html__( 'Not enough space to upload. %s KB needed.', 'unsplash' ), number_format( ( $file_size - $space_left ) / 1024 ) ),
				[ 'status' => 400 ]
			);
		}

		if ( $file_size > ( 1024 * get_site_option( 'fileupload_maxk', 1500 ) ) ) {
			return new WP_Error(
				'rest_upload_file_too_big',
				/* translators: %s: Maximum allowed file size in kilobytes. */
				sprintf( esc_html__( 'This file is too big. Files must be less than %s KB in size.', 'unsplash' ), get_site_option( 'fileupload_maxk', 1500 ) ),
				[ 'status' => 400 ]
			);
		}

		if ( ! function_exists( 'upload_is_user_over_quota' ) ) {
			// Include admin function to get access to upload_is_user_over_quota().
			require_once ABSPATH . 'wp-admin/includes/ms.php';
		}

		if ( upload_is_user_over_quota( false ) ) {
			return new WP_Error(
				'rest_upload_user_quota_exceeded',
				esc_html__( 'You have used your space quota. Please delete files before uploading.', 'unsplash' ),
				[ 'status' => 400 ]
			);
		}

		return true;
	}
}
