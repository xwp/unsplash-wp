/**
 * Internal dependencies
 */
import Button from './button';

const Toolbar = wp.media.view.Toolbar.extend( {
	initialize() {
		const state = this.controller.state(),
			selection = ( this.selection = state.get( 'selection' ) ),
			library = ( this.library = state.get( 'library' ) );

		this._views = {};

		// The toolbar is composed of two `PriorityList` views.
		this.primary = new wp.media.view.PriorityList();
		this.secondary = new wp.media.view.PriorityList();
		this.primary.$el.addClass( 'media-toolbar-primary search-form' );
		this.secondary.$el.addClass( 'media-toolbar-secondary' );

		this.views.set( [ this.secondary, this.primary ] );

		if ( this.options.items ) {
			// this.set() calls the parent's method/
			Toolbar.prototype.set.apply( this, [
				this.options.items,
				{ silent: true },
			] );
		}

		if ( ! this.options.silent ) {
			this.render();
		}

		if ( selection ) {
			selection.on( 'add remove reset', this.refresh, this );
		}

		if ( library ) {
			library.on( 'add remove reset', this.refresh, this );
		}
	},

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
				function( _view, _id ) {
					Toolbar.prototype.set.apply( this, [ _id, _view, { silent: true } ] );
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
