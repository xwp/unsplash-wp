/**
 * Internal dependencies
 */
import ImagesCollection from './collections/images_collection';
import ImagesBrowser from './images_browser';

export const withUnsplashTab = ( View ) => {
	const { tabTitle } = window.unsplash;

	return View.extend( {
		browseRouter( routerView ) {
			View.prototype.browseRouter.apply( this, arguments );

			routerView.set( {
				unsplash: {
					text: tabTitle,
					priority: 60,
				},
			} );
		},

		bindHandlers() {
			View.prototype.bindHandlers.apply( this, arguments );
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
};
