/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

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
}

/**
 * Work around that defaults the current media library to the 'Upload files' tab. This resolves the issue of the
 * Unsplash tab not being available in some media libraries, and instead showing a blank screen in the media selector.
 */
wp.media.controller.Library.prototype.defaults.contentUserSetting = false;
wp.media.controller.FeaturedImage.prototype.defaults.contentUserSetting = false;

// The nonce middleware is not available in WP 4.9, so the nonce will have to be set manually.
if ( ! wp.apiFetch.nonceMiddleware ) {
	const { wpApiSettings } = window;
	if ( wpApiSettings && wpApiSettings.nonce ) {
		const nonce = wpApiSettings.nonce;
		apiFetch.use( apiFetch.createNonceMiddleware( nonce ) );
	}
}
