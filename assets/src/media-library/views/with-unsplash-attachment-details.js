export default View => {
	return View.extend( {
		// Use the custom Unsplash template.
		template: wp.template( 'unsplash-attachment-details-two-column' ),
	} );
};
