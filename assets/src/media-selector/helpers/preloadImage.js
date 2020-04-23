/**
 * Preload image using a promise.
 *
 * @param {string} src Image source.
 * @return {Promise} Image object.
 */
const preloadImage = src => {
	return new Promise( ( resolve, reject ) => {
		const image = new window.Image();
		image.onload = () => resolve( image );
		image.onerror = reject;
		image.decoding = 'async';
		image.src = src;
	} );
};

export default preloadImage;
