/**
 * Unset the Unsplash library tab.
 *
 * @param {wp.media.controller} Controller
 * @return {wp.media.controller} Extended controller.
 */
export default Controller => {
	return Controller.extend( {
		saveContentMode() {
			if ( 'browse' !== this.get( 'router' ) ) {
				return;
			}

			const mode = this.frame.content.mode(),
				view = this.frame.router.get();

			// @todo This need to be improved.
			// Prevent persisting Unsplash as the default media tab for the user.
			if ( 'unsplash' !== mode && view && view.get( mode ) ) {
				window.setUserSetting( 'libraryContent', mode );
			}
		},
	} );
};
