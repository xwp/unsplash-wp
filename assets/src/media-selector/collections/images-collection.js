/**
 * Internal dependencies
 */
import ImagesQueryModel from '../models/images-query-model';

const ImagesCollection = wp.media.model.Attachments.extend( {
	/**
	 * Create and mirror a Query collection.
	 *
	 * @access private
	 *
	 * @param {boolean} refresh
	 */
	_requery( refresh ) {
		if ( this.props.get( 'query' ) ) {
			const props = this.props.toJSON();
			props.cache = true !== refresh;
			this.mirror( ImagesQueryModel.get( props ) );
		}
	},

	/**
	 * Get value of respSuccess from mirrored object.
	 *
	 * @return {boolean} True / false, response sucesss.
	 */
	respSuccess() {
		return this.mirroring ? this.mirroring.respSuccess() : true;
	},

	/**
	 * Get value of respErrorMessage from mirrored object.
	 *
	 * @return {Object} Error object.
	 */
	respErrorMessage() {
		return this.mirroring ? this.mirroring.respErrorMessage() : {};
	},
} );

export default ImagesCollection;
