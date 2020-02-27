/**
 * Internal dependencies
 */
import { withUnsplashTab } from './media/helpers';

// Override media frames in the respective editors to add the Unsplash tab.

/**
 * The Classic Editor makes use of the 'Post' media frame (parent of the 'Select' media frame), which contains multiple
 * media libraries (such as Gallery and Video Playlist).
 */
wp.media.view.MediaFrame.Post = withUnsplashTab( wp.media.view.MediaFrame.Post );

/**
 * The 'Select' media frame contains only one media library, and is used in Gutenberg and in other parts of WordPress
 * where selecting media is relevant (eg. image widgets, setting background image via Customizer).
 */
wp.media.view.MediaFrame.Select = withUnsplashTab( wp.media.view.MediaFrame.Select );

/**
 * Work around that defaults the current media library to the 'Upload files' tab. This resolves the issue of the
 * Unsplash tab not being available in some media libraries, and instead showing a blank screen in the media selector.
 */
wp.media.controller.Library.prototype.defaults.contentUserSetting = false;
