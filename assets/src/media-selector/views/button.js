/**
 * External dependencies
 */
import DOMPurify from 'dompurify';

/**
 * Internal dependencies
 */
import { importImages, isUnsplashImage, getConfig } from '../helpers';

const Button = wp.media.view.Button.extend( {
	/**
	 * @param {Object} event
	 */
	click( event ) {
		if ( '#' === this.attributes.href ) {
			event.preventDefault();
		}

		const state = this.controller.state();
		const selections = state.get( 'selection' );

		const hasUnsplashSelections = selections.some( attachment =>
			isUnsplashImage( attachment )
		);

		if ( ! hasUnsplashSelections ) {
			this.options.click.apply( this, arguments );
			return;
		}

		const toolbar = this.views.parent.views.parent;
		const spinner = toolbar.get( 'button-spinner' );

		this.$el.attr( 'disabled', true ); // Disable the button.
		spinner.show();

		importImages( selections )
			.then( () => {
				this.$el.attr( 'disabled', false ); // Enable button.
				spinner.hide();

				if ( this.options.click && ! this.model.get( 'disabled' ) ) {
					this.options.click.apply( this, arguments );
				}
			} )
			.catch( error => {
				this.$el.attr( 'disabled', false ); // Enable button.
				spinner.hide();
				/* istanbul ignore next */
				if ( error && error.responseJSON && error.responseJSON.message ) {
					const message = DOMPurify.sanitize( error.responseJSON.message, {
						ALLOWED_TAGS: [], // strip all HTML tags.
					} );
					console.error( message ); // eslint-disable-line
					alert( message ); // eslint-disable-line
				} else {
					const genericError = DOMPurify.sanitize(
						getConfig( 'errors' ).generic,
						{
							ALLOWED_TAGS: [], // strip all HTML tags.
						}
					);
					console.error( genericError ); // eslint-disable-line
					alert( genericError ); // eslint-disable-line
				}
			} );
	},
} );

export default Button;
