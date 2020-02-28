/**
 * Internal dependencies
 */
import ImagesSelector from './images_selector';
import unsplashState from './store/unsplash';

export const withUnsplashTab = ( View ) => {
	return View.extend( {
		createStates() {
			View.prototype.createStates.apply( this, arguments );
			this.states.add( [
				new unsplashState(),
			] );
		},
		browseRouter( routerView ) {
			View.prototype.browseRouter.apply( this, arguments );

			const state = this.state();

			// For the Classic Editor, only add the Unsplash tab to libraries that support images.
			const applicableLibraries = [ 'insert', 'gallery', 'featured-image', 'library' ];
			if ( state.id && ! applicableLibraries.includes( state.id ) ) {
				return;
			}

			const { library, mimeType } = this.options;
			const isImageIncluded = ( type ) => {
				type = Array.isArray( type ) ? type : [ type ];
				return type.includes( 'image' );
			};

			// For Gutenberg, hide the Unsplash tab if the library does not handle images.
			if ( library && library.type && ! isImageIncluded( library.type ) ) {
				return;
			}

			// For media widgets, hide the Unsplash tab if the library does not handle images.
			if ( mimeType && ! isImageIncluded( mimeType ) ) {
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
			const state = this.state( 'unsplash' );
			
			// TODO - Load selection from the correct state.
			const ogState = this.state();

			contentRegion.view = new ImagesSelector( {
				controller: this,
				collection: state.get( 'library' ),
				selection: ogState.get( 'selection' ),
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
