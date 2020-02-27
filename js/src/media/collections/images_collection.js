/**
 * Internal dependencies
 */
import Image from '../models/image_model';
import ImagesQuery from '../models/images_query_model';

const ImagesCollection = wp.media.model.Attachments.extend( {
	model: Image,

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
			props.cache = ( true !== refresh );
			this.mirror( ImagesQuery.get( props ) );
		}
	},
} );

export default ImagesCollection;
