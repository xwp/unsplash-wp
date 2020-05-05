/**
 * Internal dependencies
 */
import './style.css';
import withUnsplashTab from './helpers/with-unsplash-tab';
import unsetUnsplashLibrary from './helpers/unset-unsplash-library';
import PostFrame from './views/post-frame';

// Override media frames in the respective editors to add the Unsplash tab.
if ( wp.media && wp.media.view && wp.media.view.MediaFrame ) {
	/**
	 * The Classic Editor makes use of the 'Post' media frame (child of the 'Select' media frame), which contains multiple
	 * media libraries (such as Gallery and Video Playlist).
	 */
	if ( wp.media.view.MediaFrame.Post ) {
		wp.media.view.MediaFrame.Post = withUnsplashTab( PostFrame );
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
if ( wp.media && wp.media.controller ) {
	if ( wp.media.controller.Library ) {
		wp.media.controller.Library = unsetUnsplashLibrary(
			wp.media.controller.Library
		);
	}

	if ( wp.media.controller.FeaturedImage ) {
		wp.media.controller.FeaturedImage = unsetUnsplashLibrary(
			wp.media.controller.FeaturedImage
		);
	}
}
