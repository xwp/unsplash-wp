const ImageView = wp.media.view.Attachment.extend( {
	className: 'unsplash-attachment',
	tagName: 'div',
	buttons: {
		check: true,
	},
} );

export default ImageView;
