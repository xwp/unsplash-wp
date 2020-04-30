/**
 * Internal dependencies
 */
import './style.css';
import withUnsplashTab from './helpers/withUnsplashTab';

// Override media frames in the respective editors to add the Unsplash tab.

if ( wp.media && wp.media.view && wp.media.view.MediaFrame ) {
	/**
	 * The Classic Editor makes use of the 'Post' media frame (child of the 'Select' media frame), which contains multiple
	 * media libraries (such as Gallery and Video Playlist).
	 */
	if ( wp.media.view.MediaFrame.Post ) {
		wp.media.view.MediaFrame.Post = withUnsplashTab(
			wp.media.view.MediaFrame.Post
		);
	}
	/**
	 * The 'Select' media frame contains only one media library, and is used in Gutenberg and in other parts of WordPress
	 * where selecting media is relevant (eg. setting background image via Customizer).
	 */
	if ( wp.media.view.MediaFrame.Select ) {
		wp.media.view.MediaFrame.Select = withUnsplashTab(
			wp.media.view.MediaFrame.Select
		);
	}

	/**
	 * The 'ImageDetails' media frame is used for the replace image dialog.
	 */
	if ( wp.media.view.MediaFrame.ImageDetails ) {
		wp.media.view.MediaFrame.ImageDetails = withUnsplashTab(
			wp.media.view.MediaFrame.ImageDetails
		);
	}
}

// Ensure we don't mess the user's default media library.
if ( wp.media && wp.media.controller && wp.media.controller.Library ) {
	wp.media.controller.Library = wp.media.controller.Library.extend( {
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
				// Always default to the Unsplash tab.
				this.set( 'content', 'unsplash' );
			}
		},
	} );
}
