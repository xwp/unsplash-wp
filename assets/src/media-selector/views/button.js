import { getConfig } from '../helpers';

const Button = wp.media.view.Button.extend(
	{
		/**
		 * @return {wp.media.view.Button} Returns itself to allow chaining.
		 */
		render() {
			const model = this.model.toJSON();
			let classes = [ 'button', this.className ];

			if ( model.style ) {
				classes.push( 'button-' + model.style );
			}

			if ( model.size ) {
				classes.push( 'button-' + model.size );
			}

			classes = _.uniq( classes.concat( this.options.classes ) );
			this.el.className = classes.join( ' ' );

			this.$el.attr( 'disabled', model.disabled );
			this.$el.text( this.model.get( 'text' ) );

			return this;
		},

		/**
		 * @param {Object} event
		 */
		click( event ) {
			if ( '#' === this.attributes.href ) {
				event.preventDefault();
			}

			const state = this.controller.state();
			const selections = state.get( 'selection' );

			Button.processSelections( selections ).then( () => {
				if ( this.options.click && ! this.model.get( 'disabled' ) ) {
					this.options.click.apply( this, arguments );
				}
			} );
		},
	},
	{
		/**
		 * Import image selections.
		 *
		 * @param {wp.media.model.Selection} selections
		 * @return {Promise<Array[]>} Array of attachment data for each import.
		 */
		processSelections( selections ) {
			const imports = [];

			selections
				.filter( image => {
					return undefined === image.unsplashId;
				} )
				.forEach( image => imports.push( Button.import( image ) ) );

			return Promise.all( imports );
		},

		/**
		 * Import Unsplash image.
		 *
		 * @param { wp.media.model.Attachment } image Image model.
		 * @return {Promise} Promise.
		 */
		import( image ) {
			return new Promise( resolve => {
				const { unsplashId } = image.attributes;
				const importUrl = getConfig( 'route' ) + `/import/${ unsplashId }`;

				wp.apiRequest( {
					url: importUrl,
				} ).done( attachmentData => {
					// Update image model with actual attachment data.
					// TODO: replace only what's needed.
					const newAttrs = { ...image.attributes, ...attachmentData };
					image.set( newAttrs );

					resolve();
				} );
			} );
		},
	}
);

export default Button;
