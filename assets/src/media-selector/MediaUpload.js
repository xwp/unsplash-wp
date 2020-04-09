/**
 * WordPress dependencies
 */
import { MediaUpload } from '@wordpress/media-utils';

/**
 * Internal dependencies
 */
import UnsplashState from './store/unsplash_state';

/**
 * Copied from Gutenberg and adapted to initialize the Unsplash state.
 *
 * @see https://github.com/WordPress/gutenberg/blob/c58b32266f8c950c5b9927d286608343078aee02/packages/media-utils/src/components/media-upload/index.js#L19
 *
 * @return {wp.media.view.MediaFrame.Select} The default media workflow.
 */
const getFeaturedImageMediaFrame = () => {
	return wp.media.view.MediaFrame.Select.extend( {
		/**
		 * Enables the Set Featured Image Button.
		 *
		 * @param {Object} toolbar toolbar for featured image state
		 * @return {void}
		 */
		featuredImageToolbar( toolbar ) {
			this.createSelectToolbar( toolbar, {
				text: wp.media.view.l10n.setFeaturedImage,
				state: this.options.state,
			} );
		},

		/**
		 * Handle the edit state requirements of selected media item.
		 *
		 * @return {void}
		 */
		editState() {
			const selection = this.state( 'featured-image' ).get( 'selection' );
			const view = new wp.media.view.EditImage( {
				model: selection.single(),
				controller: this,
			} ).render();

			// Set the view to the EditImage frame using the selected image.
			this.content.set( view );

			// After bringing in the frame, load the actual editor via an ajax call.
			view.loadEditor();
		},

		/**
		 * Create the default states.
		 *
		 * @return {void}
		 */
		createStates: function createStates() {
			this.on(
				'toolbar:create:featured-image',
				this.featuredImageToolbar,
				this
			);
			this.on( 'content:render:edit-image', this.editState, this );

			this.states.add( [
				new wp.media.controller.FeaturedImage(),
				new wp.media.controller.EditImage( {
					model: this.options.editImage,
				} ),
				// And finally the reason this whole class exists, we initialize the Unsplash state.
				new UnsplashState(),
			] );
		},
	} );
};

/**
 * Copied from Gutenberg.
 *
 * @see https://github.com/WordPress/gutenberg/blob/c58b32266f8c950c5b9927d286608343078aee02/packages/media-utils/src/components/media-upload/index.js#L214-L223
 *
 * @param {Array} ids
 * @return {wp.media.model.Attachments} a new Attachments Query.
 */
const getAttachmentsCollection = ids => {
	return wp.media.query( {
		order: 'ASC',
		orderby: 'post__in',
		post__in: ids,
		posts_per_page: -1,
		query: true,
		type: 'image',
	} );
};

class MediaUploadWithUnsplashState extends MediaUpload {
	/**
	 * Initializes the Media Library requirements for the featured image flow.
	 *
	 * @return {void}
	 */
	buildAndSetFeatureImageFrame() {
		const featuredImageFrame = getFeaturedImageMediaFrame();
		const attachments = getAttachmentsCollection( this.props.value );
		const selection = new wp.media.model.Selection( attachments.models, {
			props: attachments.props.toJSON(),
		} );
		this.frame = new featuredImageFrame( {
			mimeType: this.props.allowedTypes,
			state: 'featured-image',
			multiple: this.props.multiple,
			selection,
			editing: !! this.props.value,
		} );
		wp.media.frame = this.frame;
	}
}

export default MediaUploadWithUnsplashState;
