/**
 * Get Unsplash config value.
 *
 * @param {string} configName Name of config value to retrieve.
 * @return {string|undefined} Value of config.
 */
export default configName => {
	const configData = window.unsplash;

	if ( undefined === configData ) {
		return undefined;
	}

	return configData[ configName ];
};
