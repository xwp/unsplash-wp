<?php
/**
 * Photo class.
 *
 * @package XWP\Unsplash
 */

namespace XWP\Unsplash;

/**
 * Unsplash photo model.
 */
class Photo {

	/**
	 * Get orders in which photos can be retrieved.
	 *
	 * @return array Key pair list with the values representing the name of the order type.
	 */
	public static function order_types() {
		return [
			'latest'  => __( 'Latest', 'unsplash' ),
			'oldest'  => __( 'Oldest', 'unsplash' ),
			'popular' => __( 'Popular', 'unsplash' ),
		];
	}

	// TODO: add CRUD related methods.
}
