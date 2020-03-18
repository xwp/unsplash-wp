/**
 * Internal dependencies
 */
import ImagesQueryModel from '../models/images_query_model';

const ImagesCollection = wp.media.model.Attachments.extend(
	{
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
		 * Set the default comparator only when the `orderby` property is set.
		 *
		 * @access private
		 *
		 * @param {Backbone.Model} model
		 * @param {string} orderby
		 */
		_changeOrderby( model, orderby ) {
			// If a different comparator is defined, bail.
			if (
				this.comparator &&
				this.comparator !== ImagesCollection.comparator
			) {
				return;
			}

			if ( orderby && 'post__in' !== orderby ) {
				this.comparator = ImagesCollection.comparator;
			} else {
				delete this.comparator;
			}
		},
	},
	{
		/**
		 * A function to compare two image models in the Unsplash collection.
		 *
		 * Used as the default comparator for instances of wp.media.model.Attachments
		 * and its subclasses. @see wp.media.model.Attachments._changeOrderby().
		 *
		 * @param {Backbone.Model} a
		 * @param {Backbone.Model} b
		 * @param {Object} options
		 * @return {number} -1 if the first model should come before the second,
		 *                   0 if they are of the same rank and
		 *                   1 if the first model should come after.
		 */
		comparator( a, b, options ) {
			const key = this.props.get( 'orderby' ),
				order = this.props.get( 'order' ) || 'DESC';

			let ac = a.cid,
				bc = b.cid;

			a = a.get( key );
			b = b.get( key );

			if ( 'id' === key ) {
				const prefix = 'unsplash-';

				// Strip the prefix so that the numeric ID can be compared.
				if ( a.startsWith( prefix ) && b.startsWith( prefix ) ) {
					a = parseInt( a.slice( prefix.length ), 10 );
					b = parseInt( b.slice( prefix.length ), 10 );
				}
			}

			// If `options.ties` is set, don't enforce the `cid` tiebreaker.
			if ( options && options.ties ) {
				ac = bc = null;
			}

			return 'DESC' === order
				? wp.media.compare( a, b, ac, bc )
				: wp.media.compare( b, a, bc, ac );
		},
	}
);

export default ImagesCollection;
