/**
 * Internal dependencies
 */
import ImagesCollection from '../collections/images_collection';

const ImagesQuery = wp.media.model.Query.extend( {
	initialize( models, options = {} ) {
		ImagesCollection.prototype.initialize.apply( this, arguments );

		this.args = options.args;
		this._hasMore = true;
		this.created = new Date();
		this.filters.order = () => true;
	},

	/**
	 * Overrides Backbone.Collection.sync
	 * Overrides ImagesCollection.sync
	 *
	 * @param {string} method
	 * @param {Backbone.Model} model
	 * @param {Object} options
	 * @return {Promise} Promise
	 */
	sync( method, model, options = {} ) {
		// Overload the read method so Image.fetch() functions correctly.
		if ( 'read' === method ) {
			const { route } = window.unsplash;
			// Clone the args so manipulation is non-destructive.
			const args = _.clone( this.args );

			// Determine which page to query.
			if ( -1 !== args.posts_per_page ) {
				args.paged = Math.round( this.length / args.posts_per_page ) + 1;
			}

			options.context = this;
			options.type = 'GET';
			options.url = !! args.s ? `${ route }/search/${ args.s }` : route;
			options.data = {
				page: args.paged,
				per_page: args.posts_per_page,
			};

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
	/**
	 * @readonly
	 */
	defaultArgs: {
		posts_per_page: 30,
	},

	get: ( function() {
		let queries = [];

		return ( props, options ) => {
			let	QueryObject;
			const args = {};
			const cache = !! props.cache || undefined === props.cache;

			// Remove the `query` property. This isn't linked to a query, this *is* the query.
			delete props.query;
			delete props.cache;

			// Fill default args.
			_.defaults( props, ImagesQuery.defaultProps );

			// Correct any differing property names.
			_.each( props, function( value, prop ) {
				if ( _.isNull( value ) ) {
					return;
				}

				args[ ImagesQuery.propmap[ prop ] || prop ] = value;
			} );

			// Fill any other default query args.
			_.defaults( args, ImagesQuery.defaultArgs );

			// Search the query cache for a matching query.
			if ( cache ) {
				QueryObject = _.find( queries, ( query ) => {
					return _.isEqual( query.args, args );
				} );
			} else {
				queries = [];
			}

			// Otherwise, create a new query and add it to the cache.
			if ( ! QueryObject ) {
				QueryObject = new ImagesQuery( [], _.extend( options || {}, {
					props,
					args,
				} ) );
				queries.push( QueryObject );
			}

			return QueryObject;
		};
	}() ),
} );

export default ImagesQuery;