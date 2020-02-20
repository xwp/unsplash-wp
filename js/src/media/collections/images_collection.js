/**
 * Internal dependencies
 */
import ImageModel from '../models/image_model';
import QueryModel from '../models/query_model';

const ImagesCollection = wp.media.model.Attachments.extend( {
	model: ImageModel,

	_requery( refresh ) {
		if ( this.props.get( 'query' ) ) {
			const props = this.props.toJSON();
			props.cache = ( true !== refresh );
			this.mirror( QueryModel.get( props ) );
		}
	},
} );

export default ImagesCollection;
