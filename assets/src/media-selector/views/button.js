import isUnsplashImage from '../helpers/isUnsplashImage';
import importImages from '../helpers/importImages';

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

		// Disable the button.
		this.$el.attr( 'disabled', true );
		spinner.show();

		importImages( selections ).then( () => {
			// Enable button.
			this.$el.attr( 'disabled', false );
			spinner.hide();

			if ( this.options.click && ! this.model.get( 'disabled' ) ) {
				this.options.click.apply( this, arguments );
			}
		} );
	},
} );

export default Button;
