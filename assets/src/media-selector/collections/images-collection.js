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
	 * This method has been back ported from WordPress 5.3 because of the attachments:received trigger.
	 *
	 * @see https://github.com/WordPress/wordpress-develop/blob/d05f0a86b23e37b9d97acd9317ff3fd661d64dea/src/js/media/models/attachments.js#L276-L299
	 *
	 * @param {wp.media.model.Attachments} attachments The attachments collection to mirror.
	 * @return {wp.media.model.Attachments} Returns itself to allow chaining
	 */
	mirror( attachments ) {
		if ( this.mirroring && this.mirroring === attachments ) {
			return this;
		}
		this.unmirror();
		this.mirroring = attachments;
		// Clear the collection silently. A `reset` event will be fired
		// when `observe()` calls `validateAll()`.
		this.reset( [], { silent: true } );
		this.observe( attachments );

		// Used for the search results and unsplash view.
		this.trigger( 'attachments:received', this );
		return this;
	},
	/**
	 * This method has been back ported from WordPress 5.3 because of the attachments:received trigger.
	 *
	 * @see https://github.com/WordPress/wordpress-develop/blob/d05f0a86b23e37b9d97acd9317ff3fd661d64dea/src/js/media/models/attachments.js#L311-L343
	 *
	 * @param   {Object}  [options={}]
	 * @return {Promise} Return promise object.
	 */
	more( options ) {
		const deferred = jQuery.Deferred();
		const mirroring = this.mirroring;
		const attachments = this;

		if ( ! mirroring || ! mirroring.more ) {
			return deferred.resolveWith( this ).promise();
		}
		// If we're mirroring another collection, forward `more` to
		// the mirrored collection. Account for a race condition by
		// checking if we're still mirroring that collection when
		// the request resolves.
		mirroring.more( options ).done( () => {
			deferred.resolveWith( this );

			// Used for the search results and unsplash view.
			attachments.trigger( 'attachments:received', this );
		} );

		return deferred.promise();
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
