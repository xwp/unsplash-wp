
const OrderByFilter = wp.media.view.AttachmentFilters.extend( {
	createFilters() {
		const { types } = window.unsplash.toolbar.filters.orderBy;
		const filters = { ...types };

		Object.keys( filters ).forEach( ( type ) => {
			filters[ type ] = {
				text: filters[ type ],
				props: {
					order_by: type,
				},
			};
		} );

		this.filters = filters;
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

export default OrderByFilter;
