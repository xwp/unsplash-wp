/**
 * Internal dependencies
 */
import ImagesCollection from './collections/images_collection';
import ImagesBrowser from './images_browser';

export const withUnsplashTab = ( View ) => {
	return View.extend( {
		browseRouter( routerView ) {
			View.prototype.browseRouter.apply( this, arguments );

			const state = this.state();

			// For the Classic Editor, only add the Unsplash tab to libraries that support images.
			const applicableLibraries = [ 'insert', 'gallery', 'featured-image', 'library' ];
			if ( state.id && ! applicableLibraries.includes( state.id ) ) {
				return;
			}

			// For Gutenberg, hide the Unsplash tab if the library does not handle images.
			const { library } = this.options;
			if ( library && library.type && ! library.type.includes( 'image' ) ) {
				return;
			}

			// Add the Unsplash tab to the router.
			routerView.set( {
				unsplash: {
					text: window.unsplash.tabTitle,
					priority: 60,
				},
			} );
		},

		bindHandlers() {
			View.prototype.bindHandlers.apply( this, arguments );
			this.on( 'content:create:unsplash', this.unsplashContent, this );
		},

		/**
		 * Render callback for the content region in the Unsplash tab.
		 *
		 * @param {wp.media.controller.Region} contentRegion
		 */
		unsplashContent( contentRegion ) {
			const state = this.state();

			if ( undefined === state.get( 'unsplash-collection' ) ) {
				state.set(
					'unsplash-collection',
					new ImagesCollection( null, {
						props: { orderby: 'date', query: true },
					} )
				);
			}

			contentRegion.view = new ImagesBrowser( {
				controller: this,
				collection: state.get( 'unsplash-collection' ),
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
			} );
		},
	} );
};
