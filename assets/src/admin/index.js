/* istanbul ignore file */
/**
 * Internal dependencies
 */
import './style.css';

// Override two-column frame in the media library.
if (
	wp.media &&
	wp.media.view &&
	wp.media.view.Attachment &&
	wp.media.view.Attachment.Details &&
	wp.media.view.Attachment.Details.TwoColumn
) {
	wp.media.view.Attachment.Details.TwoColumn = wp.media.view.Attachment.Details.TwoColumn.extend(
		{
			// Use the custom Unsplash template.
			template: wp.template( 'unsplash-attachment-details-two-column' ),
		}
	);
}
