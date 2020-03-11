import Macy from 'macy';

const ImageViews = wp.media.view.Attachments.extend( {
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
			useContainerForBreakpoints: true,
			margin: 0,
			columns: 3,
			breakAt: {
				992: 3,
				768: 2,
				600: 1,
			},
		} );
	},
	refreshMacy() {
		if ( this.macy ) {
			this.macy.recalculate();
		}
	},
} );

export default ImageViews;
