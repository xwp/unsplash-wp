/**
 * WordPress dependencies
 */
import { addQueryArgs } from '@wordpress/url';
/**
 * Internal dependencies
 */
import ImagesCollection from '../collections/images_collection';

const ImagesQueryModel = wp.media.model.Query.extend(
	{
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
				options.url = !! args.s
					? addQueryArgs( `${ route }/search`, { search: args.s } )
					: route;
				options.data = {
					page: args.paged,
					per_page: args.posts_per_page,
				};

				return wp.apiRequest( options );
			}

			/**
			 * Otherwise, fall back to Backbone.sync()
			 * Call wp.media.model.Attachments.sync or Backbone.sync
			 */
			const fallback = ImagesCollection.prototype.sync
				? ImagesCollection.prototype
				: Backbone;
			return fallback.sync.apply( this, arguments );
		},
	},
	{
		defaultProps: {
			orderby: 'id',
			order: 'ASC',
		},

		defaultArgs: {
			posts_per_page: 30,
		},

		get: ( function() {
			let queries = [];

			return ( props, options ) => {
				let QueryObject;
				const args = {};
				const cache = !! props.cache || undefined === props.cache;

				// Remove the `query` property. This isn't linked to a query, this *is* the query.
				delete props.query;
				delete props.cache;

				// Fill default args.
				_.defaults( props, ImagesQueryModel.defaultProps );

				// Correct any differing property names.
				_.each( props, function( value, prop ) {
					if ( _.isNull( value ) ) {
						return;
					}

					args[ ImagesQueryModel.propmap[ prop ] || prop ] = value;
				} );

				// Fill any other default query args.
				_.defaults( args, ImagesQueryModel.defaultArgs );

				// Search the query cache for a matching query.
				if ( cache ) {
					QueryObject = _.find( queries, query => {
						return _.isEqual( query.args, args );
					} );
				} else {
					queries = [];
				}

				// Otherwise, create a new query and add it to the cache.
				if ( ! QueryObject ) {
					QueryObject = new ImagesQueryModel(
						[],
						_.extend( options || {}, {
							props,
							args,
						} )
					);
					queries.push( QueryObject );
				}

				return QueryObject;
			};
		} )(),
	}
);

export default ImagesQueryModel;
