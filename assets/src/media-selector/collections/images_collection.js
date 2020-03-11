/**
 * Internal dependencies
 */
import ImagesQueryModel from '../models/images_query_model';

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
} );

export default ImagesCollection;
