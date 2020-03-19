import UnsplashState from '../store/unsplash_state';
import ImagesBrowser from '../views/images_browser';
import ImageView from '../views/image_view';
import Toolbar from '../views/toolbar';
import ToolbarSelect from '../views/toolbar_select';
import getConfig from './getConfig';

export default View => {
	return View.extend( {
		createStates() {
			View.prototype.createStates.apply( this, arguments );
			this.createUnsplashStates();
		},
		createUnsplashStates() {
			if ( ! this.unsplashStateSetup ) {
				this.states.add( [ new UnsplashState() ] );
				this.unsplashStateSetup = 1;
			}
		},
		browseRouter( routerView ) {
			View.prototype.browseRouter.apply( this, arguments );

			const state = this.state();

			// For the Classic Editor, only add the Unsplash tab to libraries that support images.
			const applicableLibraries = [ 'insert', 'featured-image', 'library' ];
			if ( state.id && ! applicableLibraries.includes( state.id ) ) {
				return;
			}

			const { library, mimeType } = this.options;
			const isImageIncluded = type => {
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
					text: getConfig( 'tabTitle' ),
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
			this.createUnsplashStates();
			const state = this.state( 'unsplash' );

			// TODO - Load selection from the correct state.
			const ogState = this.state();

			contentRegion.view = new ImagesBrowser( {
				controller: this,
				AttachmentView: ImageView,
				collection: state.get( 'library' ),
				mode: state.get( 'mode' ),
				selection: ogState.get( 'selection' ),
				model: state,
				sortable: state.get( 'sortable' ),
				search: state.get( 'searchable' ),
				filters: state.get( 'filterable' ),
				date: state.get( 'date' ),
				display: state.has( 'display' )
					? state.get( 'display' )
					: state.get( 'displaySettings' ),
				dragInfo: state.get( 'dragInfo' ),
				idealColumnWidth: state.get( 'idealColumnWidth' ),
				suggestedWidth: state.get( 'suggestedWidth' ),
				suggestedHeight: state.get( 'suggestedHeight' ),
			} );
		},

		/**
		 * Override bottom toolbar to allow for a custom button to be created. This allows us to add a callback to
		 * import Unsplash images when they are being inserted.
		 *
		 * @see wp.media.view.Toolbar
		 *
		 * @param {Object} toolbar
		 * @this wp.media.controller.Region
		 */
		createToolbar( toolbar ) {
			toolbar.view = new Toolbar( {
				controller: this,
			} );

			toolbar.view.set(
				'button-spinner',
				new wp.media.view.Spinner( {
					// TODO: Prevent the delay when showing the spinner.
					priority: 60,
				} )
			);
		},

		/**
		 * Toolbars
		 *
		 * @see wp.media.view.Toolbar.Select
		 *
		 * @param {Object} toolbar
		 * @param {Object} [options={}]
		 * @this wp.media.controller.Region
		 */
		createSelectToolbar( toolbar, options ) {
			options = options || this.options.button || {};
			options.controller = this;

			toolbar.view = new ToolbarSelect( options );

			toolbar.view.set(
				'button-spinner',
				new wp.media.view.Spinner( {
					// TODO: Prevent the delay when showing the spinner.
					priority: 60,
				} )
			);
		},
	} );
};
