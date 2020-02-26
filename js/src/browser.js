/**
 * Internal dependencies
 */
import { withUnsplashTab } from './media/helpers';

// Override media frame in Classic Editor.
wp.media.view.MediaFrame.Post = withUnsplashTab( wp.media.view.MediaFrame.Post );
// Override media frame in other places the media browser is used (such as in Gutenberg and the Customizer).
wp.media.view.MediaFrame.Select = withUnsplashTab( wp.media.view.MediaFrame.Select );

/**
 * Work around that defaults the media browser to the 'Upload files' tab. This resolves cases where the Unsplash tab is
 * not present in the media browser (such as when choosing audio or video files).
 */
wp.media.controller.Library.prototype.defaults.contentUserSetting = false;
