
import domReady from '@wordpress/dom-ready';
import { isEqual } from 'lodash';



const SelectUnsplash = ( View ) => {
	const { unsplashSettings } = window;

	return View.extend( {

		createStates() {
			View.prototype.createStates.apply( this, arguments );
				var options = this.options;
				this.states.add([
					new wp.media.controller.Library({
						id:         'unsplash',
						date: false,
						filters: false,
						searchable: false,
						library: wp.media.unsplashQuery()
					}),
				]);
		},

		browseRouter( routerView ) {
			View.prototype.browseRouter.apply( this, arguments );

			if ( this.options.mimeType && ! checkType( this.options.mimeType ) ) {
				return;
			}
			if ( this.options.library && this.options.library.type && ! checkType( this.options.library.type ) ) {
				return;
			}

			routerView.set( {
				unsplash: {
					text: unsplashSettings.tabTitle,
					priority: 60,
				},
			} );
		},
		bindHandlers() {
			View.prototype.bindHandlers.apply( this, arguments );

			this.on( 'content:render:unsplash', this.unsplashContent, this );
		},
		unsplashContent() {

			var state = this.state('unsplash'),
				view;

			console.log(state.get('library'));

			view = new wp.media.view.AttachmentsBrowser({
				controller: this,
				collection: state.get('library'),
				selection:  state.get('selection'),
				model:      state,
				sortable:   state.get('sortable'),
				search:     state.get('searchable'),
				filters:    state.get('filterable'),
				date:       state.get('date'),
				display:    state.has('display') ? state.get('display') : state.get('displaySettings'),
				dragInfo:   state.get('dragInfo'),

				idealColumnWidth: state.get('idealColumnWidth'),
				suggestedWidth:   state.get('suggestedWidth'),
				suggestedHeight:  state.get('suggestedHeight'),

			}).render();

			// Browse our library of attachments.
			this.content.set( view );
		},
	} );
};


const QueryUnsplash = () => {
	var Attachments = wp.media.model.Attachments,
		Query = wp.media.model.Query,
		UnSplashAttachments,
		UnSplashQuery;

	UnSplashQuery = Query.extend( {
		/**
		 * Overrides Backbone.Collection.sync
		 * Overrides wp.media.model.Attachments.sync
		 *
		 * @param {String} method
		 * @param {Backbone.Model} model
		 * @param {Object} [options={}]
		 * @return {Promise}
		 */
		sync: function( method, model, options ) {
			var args, fallback;

			// Overload the read method so Attachment.fetch() functions correctly.
			if ( 'read' === method ) {
				options = options || {};
				options.context = this;
				options.data = _.extend( options.data || {}, {
					action:  'query-unsplash',
					post_id: wp.media.model.settings.post.id
				});

				// Clone the args so manipulation is non-destructive.
				args = _.clone( this.args );

				// Determine which page to query.
				if ( -1 !== args.posts_per_page ) {
					args.paged = Math.round( this.length / args.posts_per_page ) + 1;
				}

				options.data.query = args;
				return wp.media.ajax( options );

				// Otherwise, fall back to Backbone.sync()
			} else {
				/**
				 * Call wp.media.model.Attachments.sync or Backbone.sync
				 */
				fallback = Attachments.prototype.sync ? Attachments.prototype : Backbone;
				return fallback.sync.apply( this, arguments );
			}
		}
	}, {
		get: (function(){

			/**
			 * @static
			 * @type Array
			 */
			var queries = [];

			/**
			 * @return {Query}
			 */
			return function( props, options ) {
				var args     = {},
					orderby  = UnSplashQuery.orderby,
					defaults = UnSplashQuery.defaultProps,
					query,
					cache    = !! props.cache || _.isUndefined( props.cache );

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
				});

				// Fill any other default query args.
				_.defaults( args, UnSplashQuery.defaultArgs );

				// `props.orderby` does not always map directly to `args.orderby`.
				// Substitute exceptions specified in orderby.keymap.
				args.orderby = orderby.valuemap[ props.orderby ] || props.orderby;

				// Search the query cache for a matching query.
				if ( cache ) {
					query = _.find( queries, function( query ) {
						return _.isEqual( query.args, args );
					});
				} else {
					queries = [];
				}

				// Otherwise, create a new query and add it to the cache.
				if ( ! query ) {
					query = new UnSplashQuery( [], _.extend( options || {}, {
						props: props,
						args:  args
					} ) );
					queries.push( query );
				}

				return query;
			};
		}())
	});

	UnSplashAttachments = Attachments.extend( {
		/**
		 * If the collection is a query, create and mirror an Attachments Query collection.
		 *
		 * @access private
		 */
		_requery: function( refresh ) {
			var props;
			if ( this.props.get('query') ) {
				props = this.props.toJSON();
				props.cache = ( true !== refresh );
				this.mirror( UnSplashQuery.get( props ) );
			}
		},
	});

	const unsplashQuery = ( props ) => {
		return new UnSplashAttachments( null, {
			props: _.extend( _.defaults( props || {}, { orderby: 'date' } ), { query: true } )
		});
	};

	return unsplashQuery;
}

const checkType = ( type ) => {
	const arr = ( type instanceof Array ) ? type : [ type ];
	return isEqual( arr, [ 'image' ] );
};

domReady( () => {
	if ( wp.media.query ) {
		wp.media.unsplashQuery = QueryUnsplash();
		console.log(wp.media.unsplashQuery());
	}

	if ( wp.media.view.MediaFrame && wp.media.view.MediaFrame.Select ) {
		const { Select } = wp.media.view.MediaFrame;
		wp.media.view.MediaFrame.Select = SelectUnsplash( Select );
	}

	if ( wp.media.view.MediaFrame && wp.media.view.MediaFrame.Post ) {
		const { Post } = wp.media.view.MediaFrame;
		wp.media.view.MediaFrame.Post = SelectUnsplash( Post );
	}

	if ( wp.mediaWidgets && wp.mediaWidgets.MediaFrameSelect ) {
		const { MediaFrameSelect } = wp.mediaWidgets;
		wp.mediaWidgets.MediaFrameSelect = SelectUnsplash( MediaFrameSelect );
	}
} );
