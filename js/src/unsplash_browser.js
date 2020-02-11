/**
 * Internal dependencies
 */
import ImagesBrowser from './media/images_browser';

const { endpoint, tabTitle } = window.unsplashSettings;
const { Post } = wp.media.view.MediaFrame;

wp.media.view.MediaFrame.Post = Post.extend( {
	browseRouter( routerView ) {
		Post.prototype.browseRouter.apply( this, arguments );

		routerView.set( {
			unsplash: {
				text: tabTitle,
				priority: 60,
			},
		} );
	},

	bindHandlers() {
		Post.prototype.bindHandlers.apply( this, arguments );
		this.on( 'content:render:unsplash', this.unsplashContent, this );
	},

	unsplashContent() {
		const state = this.state();

		const view = new ImagesBrowser( {
			controller: this,
			collection: wp.media.unsplashQuery(),
			selection: state.get( 'selection' ),
			model: state,
			sortable: state.get( 'sortable' ),
			search: state.get( 'searchable' ),
			filters: state.get( 'filterable' ),
			date: state.get( 'date' ),
			display: state.has( 'display' ) ? state.get( 'display' ) : state.get( 'displaySettings' ),
			dragInfo: state.get( 'dragInfo' ),

			idealColumnWidth: state.get( 'idealColumnWidth' ),
			suggestedWidth: state.get( 'suggestedWidth' ),
			suggestedHeight: state.get( 'suggestedHeight' ),

		} ).render();

		// Browse our library of images.
		this.content.set( view );
	},
} );

const QueryUnsplash = () => {
	const { Attachments, Query } = wp.media.model;

	const UnSplashQuery = Query.extend( {
		sync( method, model, options ) {
			// Overload the read method so Attachment.fetch() functions correctly.
			if ( 'read' === method ) {
				options = options || {};
				options.context = this;

				console.log( this.args );

				this.args.posts_per_page = 30;

				// Determine which page to query.
				/*if ( -1 !== this.args.posts_per_page ) {
					this.args.paged = Math.round( this.length / this.args.posts_per_page ) + 1;
				}*/

				// console.log(args);

				options.data = {
					order_by: this.args.order_by,
					page: this.args.paged,
					per_page: this.args.posts_per_page,
				};
				options.type = 'GET';
				options.url = !! this.args.s ? `${ endpoint }/search/${ this.args.s }` : endpoint;

				return wp.media.ajax( options );
			}

			/**
			 * Otherwise, fall back to Backbone.sync()
			 * Call wp.media.model.Attachments.sync or Backbone.sync
			 */
			const fallback = Attachments.prototype.sync ? Attachments.prototype : Backbone;
			return fallback.sync.apply( this, arguments );
		},
	}, {
		get: ( function() {
			let queries = [];

			return function( props, options ) {
				const args = {};
				let	unsplashQueryObject;
				const orderby = UnSplashQuery.orderby,
					defaults = UnSplashQuery.defaultProps,
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

					args[ UnSplashQuery.propmap[ prop ] || prop ] = value;
				} );

				// Fill any other default query args.
				_.defaults( args, UnSplashQuery.defaultArgs );

				// `props.orderby` does not always map directly to `args.orderby`.
				// Substitute exceptions specified in orderby.keymap.
				args.orderby = orderby.valuemap[ props.orderby ] || props.orderby;

				// Search the query cache for a matching query.
				if ( cache ) {
					unsplashQueryObject = _.find( queries, function( query ) {
						return _.isEqual( query.args, args );
					} );
				} else {
					queries = [];
				}

				// Otherwise, create a new query and add it to the cache.
				if ( ! unsplashQueryObject ) {
					unsplashQueryObject = new UnSplashQuery( [], _.extend( options || {}, {
						props,
						args,
					} ) );
					queries.push( unsplashQueryObject );
				}

				return unsplashQueryObject;
			};
		}() ),
	} );

	const UnSplashAttachments = Attachments.extend( {
		_requery( refresh ) {
			let props;
			if ( this.props.get( 'query' ) ) {
				props = this.props.toJSON();
				props.cache = ( true !== refresh );
				this.mirror( UnSplashQuery.get( props ) );
			}
		},
	} );

	const unsplashQuery = ( props ) => {
		return new UnSplashAttachments( null, {
			props: _.extend( _.defaults( props || {}, { orderby: 'date' } ), { query: true } ),
		} );
	};

	return unsplashQuery;
};

if ( wp.media.query ) {
	wp.media.unsplashQuery = QueryUnsplash();
}
