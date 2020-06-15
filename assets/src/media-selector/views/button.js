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
					const message = error.responseJSON.message.replace(
						/(<([^>]+)>)/gi,
						''
					);
					console.error( message ); // eslint-disable-line
					alert( message ); // eslint-disable-line
				} else {
					const errors = getConfig( 'errors' );
					console.error( errors.generic ); // eslint-disable-line
					alert( errors.generic ); // eslint-disable-line
				}
			} );
	},
} );

export default Button;
