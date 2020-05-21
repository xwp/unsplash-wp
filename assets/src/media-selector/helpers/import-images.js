/**
 * WordPress dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import isUnsplashImage from './is-unsplash-image';
import getConfig from './get-config';
import preloadImage from './preload-image';

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
	const { id, alt, title, description, caption } = image.attributes;
	const importUrl = getConfig( 'route' ) + `/import/${ id }`;
	const processUrl = getConfig( 'route' ) + '/post-process/';
	const data = {
		alt,
		title,
		description,
		caption,
	};

	return wp
		.apiRequest( { url: importUrl, data } )
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
			processImage( {
				url: addQueryArgs( processUrl + attachmentData.id, { retry: 0 } ),
				retries: 5,
				retryInterval: 500,
			} );
			return attachmentData;
		} )
		.fail( error => jQuery.Deferred().reject( { ...error, ...{ image } } ) );
};

/**
 * Process image after imported with retries.
 *
 * @param {Object} options Object of settings passed to wp.apiRequest.
 * @return {Promise} Promise.
 */
const processImage = options => {
	if ( ! options.counter ) {
		options.counter = 0;
	}
	const onFail = () => {
		if ( options.counter++ < options.retries ) {
			setTimeout(
				() =>
					processImage( {
						...options,
						url: addQueryArgs( options.url, { retry: options.counter } ),
					} ),
				options.retryInterval
			);
		}
	};
	return wp.apiRequest( options ).fail( onFail );
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
