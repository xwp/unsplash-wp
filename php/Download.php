<?php
/**
 * Download image class.
 *
 * @package XWP\Unsplash\Download.
 */

namespace XWP\Unsplash;

use WP_Error;

/**
 * Class Download
 *
 * @package XWP\Unsplash
 */
class Download {

	/**
	 * Unsplash ID.
	 *
	 * @var string
	 */
	protected $id = 0;
	/**
	 * Unsplash image array
	 *
	 * @var array
	 */
	protected $image = [];
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
	 * Processed file.
	 *
	 * @var array
	 */
	protected $file;
	/**
	 * Hardcoded file ext.
	 */
	const EXT = 'jpeg';

	/**
	 * Hardcoded MINE type.
	 */
	const MINE = 'image/jpeg';

	/**
	 * Download constructor.
	 *
	 * @param string $id Unsplash ID.
	 * @param array  $image Unsplash image array.
	 * @param string $link  URL of download image.
	 * @param int    $parent Parent ID.
	 */
	public function __construct( $id, array $image = [], $link = '', $parent = 0 ) {
		$this->id     = $id;
		$this->image  = $image;
		$this->link   = $link;
		$this->parent = $parent;
	}

	/**
	 * Process all methods in the correct order.
	 *
	 * @return array|bool|int|WP_Error
	 */
	public function process() {
		$existing_attachment = $this->get_attachment();
		if ( $existing_attachment ) {
			return $existing_attachment;
		}
		$this->process_fields();
		$this->download_image();
		$attachment = $this->create_attachment();
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
	 * Check if image already exists.
	 *
	 * @return bool|int
	 */
	public function get_attachment() {
		$check = get_page_by_path( $this->id, OBJECT, 'page' );
		if ( is_a( $check, 'WP_Post' ) ) {
			return $check->ID;
		}

		return false;
	}

	/**
	 * Process image and format data in the correct format.
	 */
	protected function process_fields() {
		$this->process_data['original_id']       = $this->get_field( 'id', wp_rand() );
		$this->process_data['description']       = $this->get_field( 'description', $this->get_field( 'alt_description' ) );
		$this->process_data['alt']               = $this->get_field( 'alt_description', $this->get_field( 'description' ) );
		$this->process_data['original_url']      = $this->get_url( 'raw' );
		$this->process_data['color']             = $this->get_field( 'color', '' );
		$this->process_data['unsplash_location'] = $this->get_field( 'location', [] );
		$this->process_data['unsplash_sponsor']  = $this->get_field( 'sponsor', [] );
		$this->process_data['unsplash_exif']     = $this->get_field( 'exif', [] );
		$this->process_data['tags']              = wp_list_pluck( $this->image['tags'], 'title' );

		$this->process_data['height']     = $this->get_field( 'height', 0 );
		$this->process_data['width']      = $this->get_field( 'width', 0 );
		$this->process_data['file']       = $this->get_field( 'id', wp_rand() ) . '.' . self::EXT;
		$this->process_data['created_at'] = $this->get_field( 'created_at', current_time( 'mysql' ) );

		$this->process_data['sizes'] = [
			'full' => [
				'height'    => $this->process_data['height'],
				'width'     => $this->process_data['width'],
				'file'      => $this->process_data['file'],
				'mime-type' => self::MINE,
			],
		];

		$this->process_data['meta'] = [
			'height'     => $this->process_data['height'],
			'width'      => $this->process_data['width'],
			'file'       => $this->process_data['file'],
			'sizes'      => $this->process_data['sizes'],
			'image_meta' => [
				'aperture'          => $this->process_data['unsplash_exif']['aperture'],
				'credit'            => $this->image['user']['name'],
				'camera'            => $this->process_data['unsplash_exif']['model'],
				'caption'           => $this->process_data['description'],
				'created_timestamp' => $this->process_data['created_at'],
				'copyright'         => $this->image['user']['name'],
				'focal_length'      => $this->process_data['unsplash_exif']['focal_length'],
				'iso'               => $this->process_data['unsplash_exif']['iso'],
				'shutter_speed'     => '0',
				'title'             => $this->process_data['alt'],
				'orientation'       => '1',
				'keywords'          => $this->process_data['tags'],
			],
		];
	}

	/**
	 * Get field from image.
	 *
	 * @param string $field Field in $image array.
	 * @param string $default Defaults to ''.
	 *
	 * @return mixed|string
	 */
	protected function get_field( $field, $default = '' ) {
		return isset( $this->image[ $field ] ) ? $this->image[ $field ] : $default;
	}

	/**
	 * Get url from image.
	 *
	 * @param string $size Size of image.
	 *
	 * @return string
	 */
	protected function get_url( $size ) {
		return isset( $this->image['urls'], $this->image['urls'][ $size ] ) ? $this->image['urls'][ $size ] : '';
	}

	/**
	 * Download image to a temp directory and move it into WP content directory.
	 *
	 * @return array|string|WP_Error
	 */
	public function download_image() {
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		$file_array = [];
		$file       = $this->link;
		$tmp        = download_url( $file );

		$file_array['name']     = $this->process_data['file'];
		$file_array['tmp_name'] = $tmp;
		$file_array['type']     = self::MINE;
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

		$this->file = wp_handle_upload( $file_array, $overrides );
		if ( isset( $this->file['error'] ) ) {
			$this->file = new WP_Error(
				'rest_upload_unknown_error',
				$this->file['error'],
				array( 'status' => 500 )
			);
		}

		return $this->file;
	}

	/**
	 * Create attachment object.
	 *
	 * @return array|int|WP_Error
	 */
	public function create_attachment() {
		if ( is_wp_error( $this->file ) ) {
			return $this->file;
		}

		$url  = $this->file['url'];
		$file = $this->file['file'];

		$attachment = new \stdClass();

		$attachment->post_name      = $this->process_data['original_id'];
		$attachment->post_content   = $this->process_data['description'];
		$attachment->post_title     = $this->process_data['alt'];
		$attachment->post_excrept   = $this->process_data['alt'];
		$attachment->post_mime_type = self::MINE;
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
			update_post_meta( $this->attachment_id, $key, $this->process_data[ $value ], true );
		}

		return;
	}

	/**
	 * Add media tags to attachment.
	 *
	 * @return array|false|WP_Error
	 */
	protected function process_tags() {
		return wp_set_post_terms( $this->attachment_id, $this->process_data['tags'], 'media_tag' );
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
	protected function process_user() {
		$unsplash_user = $this->get_field( 'user' );
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
