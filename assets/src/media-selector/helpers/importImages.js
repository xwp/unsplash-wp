/**
 * Internal dependencies
 */
import isUnsplashImage from './isUnsplashImage';
import getConfig from './getConfig';
import preloadImage from './preloadImage';

/**
 * Import selected Unsplash images.
 *
 * @param {wp.media.model.Selection[]} selections Selected attachments
 * @return {Promise<Array[]>} Array of attachment data for each imported photo.
 */
export default selections => {
	const imports = [];
	const toLoad = [];

	selections
		.filter( attachment => isUnsplashImage( attachment ) )
		.forEach( image => {
			imports.push( importImage( image ) );
			toLoad.push( preloadImageWp( image ) );
		} );

	// Force all selected image to preload. Doesn't matter is this promise is not resolved.
	Promise.all( toLoad );

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
	const processUrl = getConfig( 'route' ) + '/post-process/';

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
			wp.apiRequest( { url: processUrl + attachmentData.id } );

			return attachmentData;
		} )
		.fail( error => jQuery.Deferred().reject( { ...error, ...{ image } } ) );
};

/**
 * Preload image before inserting.
 *
 * @param { wp.media.model.Attachment } image Image model.
 *
 * @return {Promise} Promise if image size exists.
 */
const preloadImageWp = image => {
	const defaultProps = wp.media.view.settings.defaultProps;
	const imageSize =
		window.getUserSetting( 'imgsize', defaultProps.size ) || 'medium';
	const { sizes } = image.attributes;
	if ( sizes && sizes[ imageSize ] && sizes[ imageSize ].url ) {
		return preloadImage( sizes[ imageSize ].url );
	}

	return null;
};
