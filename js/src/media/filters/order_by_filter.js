
const ImageFilters = wp.media.view.AttachmentFilters.extend( {
	createFilters() {
		this.filters = {
			latest: {
				text: 'Latest',
				props: {
					order_by: 'latest',
				},
			},

			oldest: {
				text: 'Oldest',
				props: {
					order_by: 'oldest',
				},
			},

			popular: {
				text: 'Popular',
				props: {
					order_by: 'popular',
				},
			},
		};
	},

	select() {
		let value = 'latest';
		const model = this.model;
		const props = model.toJSON();

		_.find( this.filters, ( filter, id ) => {
			const equal = _.all( filter.props, ( prop, key ) => {
				return prop === ( _.isUndefined( props[ key ] ) ? null : props[ key ] );
			} );

			if ( equal ) {
				return value = id;
			}
		} );

		this.$el.val( value );
	},
} );

export default ImageFilters;
