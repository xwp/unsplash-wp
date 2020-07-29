import { select } from '@wordpress/data';

/**
 * Get current post ID.
 *
 * @return {string|undefined} Current post ID.
 */
export default () => {
	if ( select && select( 'core/editor' ) ) {
		return select( 'core/editor' ).getCurrentPostId();
	}

	const postId = document.getElementById( 'post_ID' );

	if ( postId ) {
		return postId.value;
	}

	return 0;
};
