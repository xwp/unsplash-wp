/**
 * WordPress dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import ImagesCollection from '../collections/images-collection';
import { preloadImage, getConfig } from '../helpers';

const ImagesQueryModel = wp.media.model.Query.extend(
	{
		initialize() {
			wp.media.model.Query.prototype.initialize.apply( this, arguments );

			// Add some default values.
			this._respSuccess = true;
			this._respErrorMessage = {};
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
				const route = getConfig( 'route' );
				// Clone the args so manipulation is non-destructive.
				const args = _.clone( this.args );

				// Determine which page to query.
				if ( -1 !== args.posts_per_page ) {
					args.paged = Math.round( this.length / args.posts_per_page ) + 1;
				}

				options.context = this;
				options.type = 'GET';
				options.url = !! args.s
					? addQueryArgs( `${ route }`, { search: args.s } )
					: route;
				options.data = {
					page: args.paged,
					per_page: args.posts_per_page,
				};

				// TODO: Find out how errors are displayed originally when this request fails, and apply it here.
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
		/**
		 * Value of api success.
		 *
		 * @return {boolean} True / False Of response. Defaults True.
		 */
		respSuccess() {
			return this._respSuccess;
		},

		/**
		 * Error message as object.
		 *
		 * @return {Object} Error object.
		 */
		respErrorMessage() {
			return this._respErrorMessage;
		},
		/**
		 * Fetch more attachments from the server for the collection.
		 *
		 * @param   {Object}  [options={}]
		 * @return {Promise} Return promise object.
		 */
		more( options ) {
			const query = this;

			// If there is already a request pending, return early with the Deferred object.
			if ( this._more && 'pending' === this._more.state() ) {
				return this._more;
			}

			if ( ! this.hasMore() ) {
				return jQuery
					.Deferred()
					.resolveWith( this )
					.promise();
			}

			options = options || {};

			options.remove = false;

			return ( this._more = this.fetch( options ).done( function( resp ) {
				if (
					_.isEmpty( resp ) ||
					-1 === this.args.posts_per_page ||
					resp.length < this.args.posts_per_page
				) {
					query._hasMore = false;
				}

				// If response was error, return value.
				if ( false === resp.success ) {
					query._hasMore = false;
					query._respSuccess = resp.success;
					const error = resp.data.shift();
					this._respErrorMessage = error;
				} else if ( resp.length ) {
					// Force images to load before the view is rendered.
					resp.forEach( ( { sizes } ) => {
						if ( sizes && sizes.medium && sizes.medium.url ) {
							preloadImage( sizes.medium.url );
						}
					} );
				}
			} ) );
		},
	},
	{
		defaultProps: {
			orderby: 'unsplash_order',
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
