/**
 * Internal dependencies
 */
import ImagesBrowser from './media/images_browser';
import ImagesCollection from './media/collections/images_collection';

const { tabTitle } = window.unsplash;
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

		const unsplashQuery = ( props ) => {
			return new ImagesCollection( null, {
				props: _.extend( _.defaults( props || {}, { orderby: 'date' } ), { query: true } ),
			} );
		};

		const view = new ImagesBrowser( {
			controller: this,
			collection: unsplashQuery(),
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
