/**
 * Internal dependencies
 */
import { withUnsplashAttachmentDetails } from './views';

// Override media frames in the media library.
if ( wp.media && wp.media.view ) {
	/**
	 * 	Override two-column frame in the media library.
	 */
	if (
		wp.media.view.Attachment &&
		wp.media.view.Attachment.Details &&
		wp.media.view.Attachment.Details.TwoColumn
	) {
		wp.media.view.Attachment.Details.TwoColumn = withUnsplashAttachmentDetails(
			wp.media.view.Attachment.Details.TwoColumn
		);
	}
}
