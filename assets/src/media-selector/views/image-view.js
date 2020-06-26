const ImageView = wp.media.view.Attachment.extend( {
	className: 'unsplash-attachment',
	tagName: 'div',
	buttons: {
		check: true,
	},

	/**
	 * @return {wp.media.view.Attachment} Returns itself to allow chaining.
	 */
	render() {
		const options = _.defaults(
			this.model.toJSON(),
			{
				orientation: 'landscape',
				uploading: false,
				type: '',
				subtype: '',
				icon: '',
				filename: '',
				caption: '',
				title: '',
				dateFormatted: '',
				width: '',
				height: '',
				compat: false,
				alt: '',
				description: '',
			},
			this.options
		);

		options.buttons = this.buttons;
		options.describe = this.controller.state().get( 'describe' );

		if ( 'image' === options.type ) {
			options.size = this.imageSize();
		}

		options.can = {};
		if ( options.nonces ) {
			options.can.remove = !! options.nonces.delete;
			options.can.save = !! options.nonces.update;
		}

		if ( this.controller.state().get( 'allowLocalEdits' ) ) {
			options.allowLocalEdits = true;
		}

		if ( options.uploading && ! options.percent ) {
			options.percent = 0;
		}

		this.views.detach();

		/**
		 * Whitelist because this is using the WP core `tmpl-attachment` Backbone template.
		 *
		 * @see https://github.com/WordPress/WordPress/blob/5.4-branch/wp-includes/media-template.php#L536
		 */
		this.$el.html( this.template( options ) ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
		const img = this.$el.find( '.centered img' );
		if (
			1 === img.length &&
			options.size &&
			options.size.width &&
			options.size.height &&
			options.color
		) {
			img[ 0 ].width = options.size.width;
			img[ 0 ].height = options.size.height;
			img[ 0 ].style.backgroundColor = options.color;
		}

		this.$el.toggleClass( 'uploading', options.uploading );

		if ( options.uploading ) {
			this.$bar = this.$( '.media-progress-bar div' );
		} else {
			delete this.$bar;
		}

		// Check if the model is selected.
		this.updateSelect();

		// Update the save status.
		this.updateSave();

		this.views.render();

		return this;
	},
} );

export default ImageView;
