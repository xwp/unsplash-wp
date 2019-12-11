
import domReady from '@wordpress/dom-ready';
import { isEqual } from 'lodash';

const Unsplash = wp.media.View.extend( /** @lends Unsplash.prototype */{
	className: 'unsplash',
	template: wp.template( 'unsplash' ),
} );

const SelectUnsplash = ( View ) => {
	const { unsplashSettings } = window;

	return View.extend( {
		browseRouter( routerView ) {
			View.prototype.browseRouter.apply( this, arguments );

			if ( this.options.mimeType && ! checkType( this.options.mimeType ) ) {
				return;
			}
			if ( this.options.library && this.options.library.type && ! checkType( this.options.library.type ) ) {
				return;
			}

			routerView.set( {
				unsplash: {
					text: unsplashSettings.tabTitle,
					priority: 60,
				},
			} );
		},
		bindHandlers() {
			View.prototype.bindHandlers.apply( this, arguments );

			this.on( 'content:render:unsplash', this.unsplashContent, this );
		},
		unsplashContent() {
			this.$el.removeClass( 'hide-toolbar' );
			this.content.set( new Unsplash( {
				controller: this,
			} ) );
		},
	} );
};

const checkType = ( type ) => {
	const arr = ( type instanceof Array ) ? type : [ type ];
	return isEqual( arr, [ 'image' ] );
};

domReady( () => {
	if ( wp.media.view.MediaFrame && wp.media.view.MediaFrame.Select ) {
		const { Select } = wp.media.view.MediaFrame;
		wp.media.view.MediaFrame.Select = SelectUnsplash( Select );
	}

	if ( wp.media.view.MediaFrame && wp.media.view.MediaFrame.Post ) {
		const { Post } = wp.media.view.MediaFrame;
		wp.media.view.MediaFrame.Post = SelectUnsplash( Post );
	}

	if ( wp.mediaWidgets && wp.mediaWidgets.MediaFrameSelect ) {
		const { MediaFrameSelect } = wp.mediaWidgets;
		wp.mediaWidgets.MediaFrameSelect = SelectUnsplash( MediaFrameSelect );
	}
} );
