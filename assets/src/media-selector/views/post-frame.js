const PostFrame = wp.media.view.MediaFrame.Post.extend( {
	/*
	 * Originally, the `Post` view stored the `wp.media.controller.Library` object outside of the Backbone view in a
	 * variable called `Library`. This unfortunately meant the `createStates` method would still be referring to the
	 * original library controller and not ours. Instead, we will have to fix this ourselves.
	 */
	createStates() {
		const options = this.options,
			// Refer to global `Library` so it can get our overridden view later on.
			Library = wp.media.controller.Library,
			l10n = wp.media.view.l10n;

		this.states.add( [
			// Main states.
			new Library( {
				id: 'insert',
				title: l10n.insertMediaTitle,
				priority: 20,
				toolbar: 'main-insert',
				filterable: 'all',
				library: wp.media.query( options.library ),
				multiple: options.multiple ? 'reset' : false,
				editable: true,

				// If the user isn't allowed to edit fields,
				// can they still edit it locally?
				allowLocalEdits: true,

				// Show the attachment display settings.
				displaySettings: true,
				// Update user settings when users adjust the
				// attachment display settings.
				displayUserSettings: true,
			} ),

			new Library( {
				id: 'gallery',
				title: l10n.createGalleryTitle,
				priority: 40,
				toolbar: 'main-gallery',
				filterable: 'uploaded',
				multiple: 'add',
				editable: false,

				library: wp.media.query(
					_.defaults(
						{
							type: 'image',
						},
						options.library
					)
				),
			} ),

			// Embed states.
			new wp.media.controller.Embed( { metadata: options.metadata } ),

			new wp.media.controller.EditImage( { model: options.editImage } ),

			// Gallery states.
			new wp.media.controller.GalleryEdit( {
				library: options.selection,
				editing: options.editing,
				menu: 'gallery',
			} ),

			new wp.media.controller.GalleryAdd(),

			new Library( {
				id: 'playlist',
				title: l10n.createPlaylistTitle,
				priority: 60,
				toolbar: 'main-playlist',
				filterable: 'uploaded',
				multiple: 'add',
				editable: false,

				library: wp.media.query(
					_.defaults(
						{
							type: 'audio',
						},
						options.library
					)
				),
			} ),

			// Playlist states.
			new wp.media.controller.CollectionEdit( {
				type: 'audio',
				collectionType: 'playlist',
				title: l10n.editPlaylistTitle,
				SettingsView: wp.media.view.Settings.Playlist,
				library: options.selection,
				editing: options.editing,
				menu: 'playlist',
				dragInfoText: l10n.playlistDragInfo,
				dragInfo: false,
			} ),

			new wp.media.controller.CollectionAdd( {
				type: 'audio',
				collectionType: 'playlist',
				title: l10n.addToPlaylistTitle,
			} ),

			new Library( {
				id: 'video-playlist',
				title: l10n.createVideoPlaylistTitle,
				priority: 60,
				toolbar: 'main-video-playlist',
				filterable: 'uploaded',
				multiple: 'add',
				editable: false,

				library: wp.media.query(
					_.defaults(
						{
							type: 'video',
						},
						options.library
					)
				),
			} ),

			new wp.media.controller.CollectionEdit( {
				type: 'video',
				collectionType: 'playlist',
				title: l10n.editVideoPlaylistTitle,
				SettingsView: wp.media.view.Settings.Playlist,
				library: options.selection,
				editing: options.editing,
				menu: 'video-playlist',
				dragInfoText: l10n.videoPlaylistDragInfo,
				dragInfo: false,
			} ),

			new wp.media.controller.CollectionAdd( {
				type: 'video',
				collectionType: 'playlist',
				title: l10n.addToVideoPlaylistTitle,
			} ),
		] );

		if ( wp.media.view.settings.post.featuredImageId ) {
			this.states.add( new wp.media.controller.FeaturedImage() );
		}
	},
} );

export default PostFrame;
