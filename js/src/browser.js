/**
 * Internal dependencies
 */
import { withUnsplashTab } from './media/helpers';

// Override media frame in Classic Editor.
wp.media.view.MediaFrame.Post = withUnsplashTab( wp.media.view.MediaFrame.Post );
// Override media frame in other places the media browser is used (such as in Gutenberg and the Customizer).
wp.media.view.MediaFrame.Select = withUnsplashTab( wp.media.view.MediaFrame.Select );
