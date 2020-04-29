/**
 * Internal dependencies
 */
import isUnsplashImage from './isUnsplashImage';
import getConfig from './getConfig';
import isBlockEditor from './isBlockEditor';

/**
 * Import selected Unsplash images.
 *
 * @param {wp.media.model.Selection[]} selections Selected attachments
 * @return {Promise<Array[]>} Array of attachment data for each imported photo.
 */
export default selections => {
	const imports = [];

	selections
		.filter( attachment => isUnsplashImage( attachment ) )
		.forEach( image => imports.push( importImage( image ) ) );

	return Promise.all( imports );
};

/**
 * Import Unsplash image.
 *
 * @param { wp.media.model.Attachment } image Image model.
 * @return {Promise} Promise.
 */
const importImage = image => {
	const { id } = image.attributes;
	const importUrl = getConfig( 'route' ) + `/import/${ id }`;

	return wp
		.apiRequest( { url: importUrl } )
		.done( attachmentData => {
			// Update image ID from imported attachment. This will be used to fetch the <img> tag.
			// Note: `image.set()` is called rather than updating `image.id` directly so that potential Backbone event listeners can be fired.
			image.set( {
				...image.attributes,
				...{
					id: attachmentData.id,
					url: attachmentData.source_url,
					nonces: attachmentData.nonces,
				},
			} );
			postProcessImage( attachmentData.id, image.attributes.filename );
			return attachmentData;
		} )
		.fail( error => jQuery.Deferred().reject( { ...error, ...{ image } } ) );
};

const postProcessImage = ( attachmentId, imageFileName ) => {
	const processUrl = getConfig( 'route' ) + `/post-process/${ attachmentId }`;

	wp.apiRequest( { url: processUrl } ).fail( () => {
		const errorMessage = _.template(
			getConfig( 'errorMessages' ).postProcess
		)( { filename: imageFileName } );

		if ( isBlockEditor() ) {
			wp.data.dispatch( 'core/notices' ).createNotice( 'error', errorMessage, {
				isDismissible: true,
			} );
		} else {
			jQuery(
				`<div class="notice notice-error is-dismissible"><p>${ errorMessage }</p></div>`
			).insertAfter( '.notice' );

			// Let WordPress add a dismissible button to the new notice.
			jQuery( document ).trigger( 'wp-updates-notice-added' );
		}
	} );
};
