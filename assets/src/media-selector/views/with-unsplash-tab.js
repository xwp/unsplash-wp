/**
 * Internal dependencies
 */
import UnsplashState from '../store/unsplash-state';
import ImagesBrowser from './images-browser';
import ImageView from './image-view';
import Toolbar from './toolbar';
import ToolbarSelect from './toolbar-select';
import { getConfig, isApplicableLibraries, isImageIncluded } from '../helpers';

export default View => {
	return View.extend( {
		createStates() {
			View.prototype.createStates.apply( this, arguments );
			this.states.add( [ new UnsplashState() ] );
		},

		browseRouter( routerView ) {
			View.prototype.browseRouter.apply( this, arguments );

			const state = this.state();

			// For the Classic Editor, only add the Unsplash tab to libraries that support images.
			if ( ! isApplicableLibraries( state.id ) ) {
				return;
			}

			const { library, mimeType } = this.options;

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
		 * Override bottom toolbar for replace image view.
		 *
		 * @see wp.media.view.MediaFrame.ImageDetails
		 *
		 * @this wp.media.view.MediaFrame.ImageDetails
		 */
		renderReplaceImageToolbar() {
			const frame = this;
			const lastState = frame.lastState();
			const previous = lastState && lastState.id;

			const toolbar = new Toolbar( {
				controller: this,
				items: {
					back: {
						text: wp.media.view.l10n.back,
						priority: 80,
						click() {
							if ( previous ) {
								frame.setState( previous );
							} else {
								frame.close();
							}
						},
					},
					replace: {
						style: 'primary',
						text: wp.media.view.l10n.replace,
						priority: 20,
						requires: { selection: true },

						click() {
							const controller = this.controller;
							const state = controller.state();
							const selection = state.get( 'selection' );
							const attachment = selection.single();

							controller.close();

							controller.image.changeAttachment(
								attachment,
								state.display( attachment )
							);

							// Not sure if we want to use wp.media.string.image which will create a shortcode or
							// perhaps wp.html.string to at least to build the <img />.
							state.trigger( 'replace', controller.image.toJSON() );

							// Restore and reset the default state.
							controller.setState( controller.options.state );
							controller.reset();
						},
					},
				},
			} );

			toolbar.set(
				'button-spinner',
				new wp.media.view.Spinner( {
					// TODO: Prevent the delay when showing the spinner.
					priority: 10,
				} )
			);

			this.toolbar.set( toolbar );
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
