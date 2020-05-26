import getConfig from '../helpers/get-config';

const Select = wp.media.view.MediaFrame.Select;

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

		// Add the default states.
		this.states.add( [
			// Main states.
			new wp.media.controller.Library( {
				library: wp.media.query( options.library ),
				multiple: options.multiple,
				title: getConfig( 'tabTitle' ),
				priority: 20,
			} ),
			new wp.media.controller.EditImage( { model: options.editImage } ),
		] );
	},
} );

export default Unsplash;
