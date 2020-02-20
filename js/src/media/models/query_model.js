/**
 * Internal dependencies
 */
import ImagesCollection from '../collections/images_collection';

const QueryModel = wp.media.model.Query.extend( {
	sync( method, model, options ) {
		// Overload the read method so Attachment.fetch() functions correctly.
		if ( 'read' === method ) {
			const { route } = window.unsplash;

			options = options || {};
			options.context = this;

			this.args.posts_per_page = 30;

			// Determine which page to query.
			if ( -1 !== this.args.posts_per_page ) {
				this.args.paged = Math.round( this.length / this.args.posts_per_page ) + 1;
			}

			options.data = {
				order_by: this.args.order_by,
				page: this.args.paged,
				per_page: this.args.posts_per_page,
			};
			options.type = 'GET';
			options.url = !! this.args.s ? `${ route }/search/${ this.args.s }` : route;

			return wp.media.ajax( options );
		}

		/**
		 * Otherwise, fall back to Backbone.sync()
		 * Call wp.media.model.Attachments.sync or Backbone.sync
		 */
		const fallback = ImagesCollection.prototype.sync ? ImagesCollection.prototype : Backbone;
		return fallback.sync.apply( this, arguments );
	},
}, {
	get: ( function() {
		let queries = [];

		return function( props, options ) {
			const args = {};
			let	QueryObject;
			const orderby = QueryModel.orderby,
				defaults = QueryModel.defaultProps,
				cache = !! props.cache || _.isUndefined( props.cache );

			// Remove the `query` property. This isn't linked to a query,
			// this *is* the query.
			delete props.query;
			delete props.cache;

			// Fill default args.
			_.defaults( props, defaults );

			// Normalize the order.
			props.order = props.order.toUpperCase();
			if ( 'DESC' !== props.order && 'ASC' !== props.order ) {
				props.order = defaults.order.toUpperCase();
			}

			// Ensure we have a valid orderby value.
			if ( ! _.contains( orderby.allowed, props.orderby ) ) {
				props.orderby = defaults.orderby;
			}

			_.each( [ 'include', 'exclude' ], function( prop ) {
				if ( props[ prop ] && ! _.isArray( props[ prop ] ) ) {
					props[ prop ] = [ props[ prop ] ];
				}
			} );

			// Generate the query `args` object.
			// Correct any differing property names.
			_.each( props, function( value, prop ) {
				if ( _.isNull( value ) ) {
					return;
				}

				args[ QueryModel.propmap[ prop ] || prop ] = value;
			} );

			// Fill any other default query args.
			_.defaults( args, QueryModel.defaultArgs );

			// `props.orderby` does not always map directly to `args.orderby`.
			// Substitute exceptions specified in orderby.keymap.
			args.orderby = orderby.valuemap[ props.orderby ] || props.orderby;

			// Search the query cache for a matching query.
			if ( cache ) {
				QueryObject = _.find( queries, function( query ) {
					return _.isEqual( query.args, args );
				} );
			} else {
				queries = [];
			}

			// Otherwise, create a new query and add it to the cache.
			if ( ! QueryObject ) {
				QueryObject = new QueryModel( [], _.extend( options || {}, {
					props,
					args,
				} ) );
				queries.push( QueryObject );
			}

			return QueryObject;
		};
	}() ),
} );

export default QueryModel;
