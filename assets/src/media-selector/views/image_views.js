import Macy from 'macy';

const Attachments = wp.media.view.Attachments.extend( {
	className: 'attachments',
	tagName: 'div',
	macy: null,

	render() {
		wp.media.view.Attachments.prototype.render.apply( this, arguments );
		this.refreshMacy();
	},
	ready() {
		this.setupMacy();
		this.scroll();
	},
	setupMacy() {
		this.macy = Macy( {
			container: '#' + this.el.id,
			trueOrder: true,
			waitForImages: true,
			margin: 10,
			columns: 3,
			breakAt: {
				1400: 2,
				1024: 1,
			},
		} );
	},
	refreshMacy() {
		if ( this.macy ) {
			this.macy.recalculate();
		}
	},
} );

export default Attachments;
