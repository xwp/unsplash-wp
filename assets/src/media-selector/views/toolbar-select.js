/**
 * Internal dependencies
 */
import { Toolbar } from './';

const ToolbarSelect = wp.media.view.Toolbar.Select.extend( {
	initialize() {
		const options = this.options;
		const l10n = wp.media.view.l10n;

		_.bindAll( this, 'clickSelect' );

		_.defaults( options, {
			event: 'select',
			state: false,
			reset: true,
			close: true,
			text: l10n.select,

			// Does the button rely on the selection?
			requires: {
				selection: true,
			},
		} );

		options.items = _.defaults( options.items || {}, {
			select: {
				style: 'primary',
				text: options.text,
				priority: 80,
				click: this.clickSelect,
				requires: options.requires,
			},
		} );
		// Call 'initialize' directly on our custom Toolbar class.
		Toolbar.prototype.initialize.apply( this, arguments );
	},
} );

export default ToolbarSelect;
