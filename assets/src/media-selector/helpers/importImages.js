import isUnsplashImage from './isUnsplashImage';
import getConfig from './getConfig';

/**
 * Import selected Unsplash images.
 *
 * @param {wp.media.model.Selection[]} selections Selected attachments
 * @return {Promise<Array[]>} Array of attachment data for each import.
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
	return new Promise( resolve => {
		const { unsplashId } = image.attributes;
		const importUrl = getConfig( 'route' ) + `/import/${ unsplashId }`;

		wp.apiRequest( {
			url: importUrl,
		} ).done( attachmentData => {
			// Update image ID from imported attachment. This will be used to fetch the <img> tag.
			// image.attributes.id = attachmentData.id;
			image.set( { ...image.attributes, ...{ id: attachmentData.id } } );

			resolve( attachmentData );
		} );
	} );
};
