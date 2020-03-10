/**
 * Internal dependencies
 */
import Button from './button';

const Toolbar = wp.media.view.Toolbar.extend( {
	/**
	 * @param {string} id
	 * @param {Backbone.View|Object} view
	 * @param {Object} [options={}]
	 * @return {wp.media.view.Toolbar} Returns itself to allow chaining.
	 */
	set( id, view, options ) {
		let list;
		options = options || {};

		// Accept an object with an `id` : `view` mapping.
		if ( _.isObject( id ) ) {
			_.each(
				id,
				function( view, id ) {
					this.set( id, view, { silent: true } );
				},
				this
			);
		} else {
			if ( ! ( view instanceof Backbone.View ) ) {
				view.classes = [ 'media-button-' + id ].concat( view.classes || [] );
				view = new Button( view ).render();
			}

			view.controller = view.controller || this.controller;

			this._views[ id ] = view;

			list = view.options.priority < 0 ? 'secondary' : 'primary';
			this[ list ].set( id, view, options );
		}

		if ( ! options.silent ) {
			this.refresh();
		}

		return this;
	},
} );

export default Toolbar;
