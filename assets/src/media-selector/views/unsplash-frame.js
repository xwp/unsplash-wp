/**
 * Internal dependencies
 */
import { getConfig } from '../helpers';
import unsetUnsplashLibrary from '../controllers/unset-unsplash-library';

const Select = wp.media.view.MediaFrame.Select;
let Library = wp.media.controller.Library;
let EditImage = wp.media.controller.EditImage;

/**
 * wp.media.view.MediaFrame.Unsplash
 *
 * Create a custom select view for unsplash.
 *
 * @class
 * @augments wp.media.view.MediaFrame
 * @augments wp.media.view.Frame
 * @augments wp.media.View
 * @augments wp.Backbone.View
 * @augments Backbone.View
 * @mixes wp.media.controller.StateMachine
 */
const Unsplash = Select.extend( {
	className: 'media-frame unsplash-media-frame',
	initialize() {
		// Call 'initialize' directly on the parent class.
		Select.prototype.initialize.apply( this, arguments );

		_.defaults( this.options, {
			selection: [],
			library: {},
			multiple: false,
			state: 'unsplash',
		} );

		this.createStates();
		this.bindHandlers();
	},

	/**
	 * Bind region mode event callbacks.
	 *
	 * @see media.controller.Region.render
	 */
	bindHandlers() {
		this.on( 'toolbar:create:select', this.createSelectToolbar, this );
		this.on( 'content:render:edit-image', this.editImageContent, this );
	},
	/**
	 * Create the default states on the frame.
	 */
	createStates() {
		const options = this.options;

		if ( this.options.states ) {
			return;
		}

		Library = unsetUnsplashLibrary( Library );
		EditImage = unsetUnsplashLibrary( EditImage );

		// Add the default states.
		this.states.add( [
			// Main states.
			new Library( {
				library: wp.media.query( options.library ),
				multiple: options.multiple,
				title: getConfig( 'tabTitle' ),
				isUnsplash: true,
				priority: 20,
			} ),
			new EditImage( { model: options.editImage } ),
		] );
	},
} );

export default Unsplash;
