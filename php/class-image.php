<?php
/**
 * Process image class.
 *
 * @package Unsplash.
 */

namespace Unsplash;

/**
 * Class Image
 *
 * @package Unsplash
 */
class Image {

	/**
	 * Unsplash image array.
	 *
	 * @var Image
	 */
	protected $image;
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
	 * Image constructor.
	 *
	 * @param array $image Unsplash image array.
	 */
	public function __construct( array $image = [] ) {
		$this->image = $image;
		$this->process_fields();
	}

	/**
	 * Process image and format data in the correct format.
	 */
	public function process_fields() {
		$this->process_data['original_id']       = strtolower( $this->get_image_field( 'unsplash_id', $this->get_image_field( 'id' ) ) );
		$this->process_data['description']       = $this->get_image_field( 'description', $this->get_image_field( 'alt_description' ) );
		$this->process_data['alt']               = $this->get_image_field( 'alt_description', $this->get_image_field( 'description' ) );
		$this->process_data['original_url']      = $this->get_image_url( 'raw' );
		$this->process_data['caption']           = $this->get_caption();
		$this->process_data['color']             = $this->get_image_field( 'color', '' );
		$this->process_data['unsplash_location'] = $this->get_image_field( 'location', [] );
		$this->process_data['unsplash_sponsor']  = $this->get_image_field( 'sponsor', [] );
		$this->process_data['unsplash_exif']     = $this->get_image_field(
			'exif',
			[
				'aperture'     => '',
				'model'        => '',
				'focal_length' => '',
				'iso'          => '',
			]
		);
		$this->process_data['tags']              = wp_list_pluck( $this->get_image_field( 'tags', [] ), 'title' );
		$this->process_data['mime_type']         = self::MIME;
		$this->process_data['ext']               = self::EXT;
		$this->process_data['file']              = $this->process_data['original_id'] . '.' . $this->process_data['ext'];
		$this->process_data['height']            = $this->get_image_field( 'height', 0 );
		$this->process_data['width']             = $this->get_image_field( 'width', 0 );
		$this->process_data['created_at']        = $this->get_image_field( 'created_at', current_time( 'mysql' ) );
		$this->process_data['updated_at']        = $this->get_image_field( 'updated_at', current_time( 'mysql' ) );
		$this->process_data['links']             = $this->get_image_field( 'links', [ 'html' => '' ] );
		$this->process_data['user']              = $this->get_image_field(
			'user',
			[
				'name' => '',
				'id'   => '',
				'bio'  => '',
			]
		);
		$this->process_data['sizes']             = [
			'full' => [
				'height'    => $this->process_data['height'],
				'width'     => $this->process_data['width'],
				'file'      => $this->process_data['file'],
				'mime-type' => $this->process_data['mime_type'],
			],
		];

		$this->process_data['meta'] = [
			'height'     => $this->process_data['height'],
			'width'      => $this->process_data['width'],
			'file'       => $this->process_data['file'],
			'sizes'      => $this->process_data['sizes'],
			'image_meta' => [
				'aperture'          => $this->process_data['unsplash_exif']['aperture'],
				'credit'            => $this->process_data['user']['name'],
				'camera'            => $this->process_data['unsplash_exif']['model'],
				'caption'           => $this->process_data['description'],
				'created_timestamp' => $this->process_data['created_at'],
				'copyright'         => $this->process_data['user']['name'],
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
	 * Get field from process data.
	 *
	 * @param string $field Field in $process_data array.
	 * @param string $default Defaults to ''.
	 *
	 * @return mixed|string
	 */
	public function get_field( $field, $default = '' ) {
		return isset( $this->process_data[ $field ] ) ? $this->process_data[ $field ] : $default;
	}

	/**
	 * Get field from image.
	 *
	 * @param string $field Field in $image array.
	 * @param string $default Defaults to ''.
	 *
	 * @return mixed|string
	 */
	public function get_image_field( $field, $default = '' ) {
		return ( isset( $this->image[ $field ] ) && ! empty( $this->image[ $field ] ) ) ? $this->image[ $field ] : $default;
	}

	/**
	 * Get url from image.
	 *
	 * @param string $size Size of image.
	 *
	 * @return string
	 */
	public function get_image_url( $size ) {
		return isset( $this->image['urls'], $this->image['urls'][ $size ] ) ? $this->image['urls'][ $size ] : '';
	}

	/**
	 * Return a formatted caption.
	 *
	 * @return string Formatted caption.
	 */
	public function get_caption() {
		if ( ! isset( $this->image['user'] ) || empty( $this->image['user'] ) ) {
			return '';
		}

		$user_url  = ( isset( $this->image['user']['links']['html'] ) ) ? $this->image['user']['links']['html'] : '';
		$user_name = ( isset( $this->image['user']['name'] ) ) ? $this->image['user']['name'] : '';

		if ( empty( $user_url ) || empty( $user_name ) ) {
			return '';
		}

		$options     = get_option( 'unsplash_settings' );
		$default_utm = ( getenv( 'UNSPLASH_UTM_SOURCE' ) ) ? getenv( 'UNSPLASH_UTM_SOURCE' ) : 'WordPress-XWP';
		$utm_source  = ! empty( $options['utm_source'] ) ? $options['utm_source'] : $default_utm;

		$url = add_query_arg(
			[
				'utm_source' => $utm_source,
				'utm_medium' => 'referral',
			],
			'https://unsplash.com/'
		);
		/* translators: 1: User URL, 2: User's name, 3: Unsplash URL */
		return sprintf( __( 'Photo by <a href="%1$s">%2$s</a> on <a href="%3$s">Unsplash</a> ', 'unsplash' ), esc_url( $user_url ), $user_name, esc_url( $url ) );
	}
}
