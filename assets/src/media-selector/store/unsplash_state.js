/**
 * Internal dependencies
 */
import ImagesCollection from '../views/collections/images_collection';

const State = wp.media.controller.State;

const UnsplashState = State.extend( {
	defaults: {
		id: 'unsplash',
		toolbar: 'unsplash',
		title: window.unsplash.tabTitle,
		content: 'unsplash',
		menu: 'unsplash',
		router: false,
		priority: -60,
		filterable: 'uploaded',
		multiple: 'add',
		editable: false,
	},

	initialize() {
		const selection = this.get( 'selection' );
		let props;

		if ( ! this.get( 'library' ) ) {
			this.set(
				'library',
				new ImagesCollection( null, {
					props: { orderby: 'id', order: 'ASC', query: true },
				} )
			);
		}

		if ( ! ( selection instanceof wp.media.model.Selection ) ) {
			props = selection;

			if ( ! props ) {
				props = this.get( 'library' ).props.toJSON();
				props = _.omit( props, 'orderby', 'query' );
			}

			this.set(
				'selection',
				new wp.media.model.Selection( null, {
					multiple: this.get( 'multiple' ),
					props,
				} )
			);
		}
		State.prototype.initialize.apply( this, arguments );
	},
} );

export default UnsplashState;
