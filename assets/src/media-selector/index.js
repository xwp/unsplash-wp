/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import './style.css';
import withUnsplashTab from './helpers/withUnsplashTab';
import MediaUpload from './MediaUpload';

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
}

/**
 * We can't override the featured image media frame Gutenberg by extending Backbone views, meaning we can't initialize
 * our own state to load the Unsplash tab. Instead, we have to filter the `editor.MediaUpload` component
 * (which is used by `editor.PostFeaturedImage`) and extend it to initialize our state.
 */
addFilter(
	'editor.MediaUpload',
	'unsplash/extend-featured-image',
	() => MediaUpload
);

/**
 * Work around that defaults the current media library to the 'Upload files' tab. This resolves the issue of the
 * Unsplash tab not being available in some media libraries, and instead showing a blank screen in the media selector.
 */
wp.media.controller.Library.prototype.defaults.contentUserSetting = false;
