
const { Select } = wp.media.view.MediaFrame;
const { l10n } = wp.media.view;
const { unsplashSettings } = window;

const SelectUnsplash = Select.extend( {
	browseRouter: function( routerView ) {
		Select.prototype.browseRouter.apply( this, arguments );

		routerView.set({
			unsplash: {
				text: unsplashSettings.tabTitle,
				priority: 60
			}
		});
	},
	bindHandlers: function() {
		Select.prototype.bindHandlers.apply( this, arguments );

		this.on( 'content:render:unsplash', this.unsplashContent, this );
	},
	unsplashContent: function() {
		this.$el.removeClass( 'hide-toolbar' );
		this.content.set( new Unsplash({
			controller: this
		}) );
	},
});

const Unsplash = wp.media.View.extend(/** @lends Unsplash.prototype */{
	className: 'unsplash',
	template:  wp.template('unsplash')
});

wp.media.view.MediaFrame.Select = SelectUnsplash;
