/**
 * Determine if selected attachment is an Unsplash image.
 *
 * @param {wp.media.model.Attachment} attachment
 * @return {boolean} True if Unsplash image, else false.
 */
export default attachment => {
	return (
		attachment.attributes && undefined !== attachment.attributes.unsplashId
	);
};
