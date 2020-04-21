import Macy from 'macy';

const ImageViews = wp.media.view.Attachments.extend( {
	className: 'attachments',
	tagName: 'div',
	macy: null,

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
			margin: 24,
			columns: 3,
			breakAt: {
				992: 3,
				768: 2,
				600: 1,
			},
		} );
	},

	recalculateLayout() {
		if ( this.macy ) {
			// Only recalculate layout when all images in the container have been loaded.
			this.macy.recalculateOnImageLoad( true );
			// Recalculate layout for images that have been added while scrolling.
			this.macy.recalculate();
		}
	},
} );

export default ImageViews;
