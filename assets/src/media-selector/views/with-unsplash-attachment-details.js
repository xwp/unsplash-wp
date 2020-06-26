export default View => {
	return View.extend( {
		className: 'attachment-details unsplash-attachment-details',
		template: wp.template( 'unsplash-attachment-details' ),
	} );
};
