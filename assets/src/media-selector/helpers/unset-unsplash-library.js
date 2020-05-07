/**
 * Internal dependencies
 */
import isApplicableLibraries from './isApplicableLibraries';
import isImageIncluded from './isImageIncluded';

const settingName = 'libraryContentWithUnsplash';

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

			// Prevent persisting Unsplash as the default media tab for the user.
			if ( 'unsplash' !== mode && view && view.get( mode ) ) {
				window.setUserSetting( 'libraryContent', mode );
			}

			// If current view supports the Unsplash tab save in user settings with different key.
			const id = this.attributes.id;
			const library = this.get( 'library' );
			const {
				props: {
					attributes: { type },
				},
			} = library;

			if (
				! isApplicableLibraries( id ) ||
				( library && type && ! isImageIncluded( type ) )
			) {
				return;
			}
			window.setUserSetting( settingName, mode );
		},
		activate() {
			this.syncSelection();

			wp.Uploader.queue.on( 'add', this.uploading, this );

			this.get( 'selection' ).on(
				'add remove reset',
				this.refreshContent,
				this
			);

			if ( this.get( 'router' ) && this.get( 'contentUserSetting' ) ) {
				this.frame.on( 'content:activate', this.saveContentMode, this );
				const id = this.attributes.id;
				const library = this.get( 'library' );
				const {
					props: {
						attributes: { type },
					},
				} = library;

				// If current view supports the Unsplash tab load from a user settings with different key.
				if (
					! isApplicableLibraries( id ) ||
					( library && type && ! isImageIncluded( type ) )
				) {
					this.set(
						'content',
						window.getUserSetting( 'libraryContent', this.get( 'content' ) )
					);
				} else {
					this.set(
						'content',
						window.getUserSetting( settingName, this.get( 'content' ) )
					);
				}
			}
		},
	} );
};
