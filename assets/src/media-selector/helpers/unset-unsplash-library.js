/**
 * Preload image using a promise.
 *
 * @param {wp.media.controller} Controller Image source.
 * @return {Promise} Image object.
 */
export default Controller => {
	return Controller.extend( {
		saveContentMode() {
			if ( 'browse' !== this.get( 'router' ) ) {
				return;
			}

			const mode = this.frame.content.mode(),
				view = this.frame.router.get();

			// @todo Why are we doing this, should we set Unsplash as the default if no mode is set?
			// Prevent persisting Unsplash as the default media tab for the user.
			if ( 'unsplash' !== mode && view && view.get( mode ) ) {
				window.setUserSetting( 'libraryContent', mode );
			}
		},
	} );
};
