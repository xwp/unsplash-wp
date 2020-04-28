import ImageViews from './image_views';
import getConfig from '../helpers/getConfig';

const ImagesBrowser = wp.media.view.AttachmentsBrowser.extend( {
	className: 'unsplash-browser attachments-browser',

	initialize() {
		wp.media.view.AttachmentsBrowser.prototype.initialize.apply(
			this,
			arguments
		);

		this.collection.on( 'add remove reset', this.focusInput, this );

		// Update masonry layout only when a set of images (new page) is loaded.
		this.collection.on( 'attachments:received remove', () =>
			this.attachments.recalculateLayout()
		);
		this.collection.on(
			'add remove reset attachments:received',
			this.showError,
			this
		);
	},

	createToolbar() {
		const toolbar = getConfig( 'toolbar' );

		this.toolbar = new wp.media.view.Toolbar( {
			controller: this.controller,
		} );

		this.views.add( this.toolbar );

		// Label for the 'search' filter. This is a visually hidden element and needs to be rendered before.
		this.toolbar.set(
			'searchLabel',
			new wp.media.view.Label( {
				value: toolbar.filters.search.label,
				className: 'unsplash-search-input-label',
				attributes: {
					for: 'media-search-input',
				},
				priority: 50,
			} ).render()
		);

		this.searchFilter = new wp.media.view.Search( {
			controller: this.controller,
			model: this.collection.props,
			priority: 60,
			className: 'unsplash-search',
			id: 'unsplash-search-input',
			attributes: {
				type: 'search',
				placeholder: toolbar.filters.search.placeholder,
				autofocus: true,
			},
		} );

		// Create search filter.
		this.toolbar.set( 'searchFilter', this.searchFilter.render() );

		// TODO: replace with better loading indicator.
		this.toolbar.set(
			'spinner',
			new wp.media.view.Spinner( {
				priority: 55,
			} )
		);
	},

	createSingle() {
		wp.media.view.AttachmentsBrowser.prototype.createSingle.apply(
			this,
			arguments
		);

		const sidebar = this.sidebar,
			single = this.options.selection.single();

		sidebar.set(
			'details',
			new wp.media.view.Attachment.Details( {
				controller: this.controller,
				model: single,
				priority: 80,
				allowLocalEdits: true,
			} )
		);
	},

	createAttachments() {
		const noResults = getConfig( 'noResults' );

		this.attachments = new ImageViews( {
			controller: this.controller,
			collection: this.collection,
			selection: this.options.selection,
			model: this.model,
			sortable: this.options.sortable,
			scrollElement: this.options.scrollElement,
			idealColumnWidth: this.options.idealColumnWidth,
			refreshThreshold: 9,

			// The single `Attachment` view to be used in the `Attachments` view.
			AttachmentView: this.options.AttachmentView,
		} );

		// Add keydown listener to the instance of the Attachments view
		this.controller.on(
			'attachment:keydown:arrow',
			_.bind( this.attachments.arrowEvent, this.attachments )
		);
		this.controller.on(
			'attachment:details:shift-tab',
			_.bind( this.attachments.restoreFocus, this.attachments )
		);

		this.views.add( this.attachments );

		this.attachmentsNoResults = new wp.media.View( {
			controller: this.controller,
			tagName: 'div',
		} );

		this.attachmentsNoResults.$el.addClass( 'hidden no-media' );
		this.attachmentsNoResults.$el.append( `<h2>${ noResults.noMedia }</h2>` );

		this.views.add( this.attachmentsNoResults );

		this.attachmentsError = new wp.media.View( {
			controller: this.controller,
			tagName: 'div',
		} );
		this.attachmentsError.$el.addClass(
			'hidden notice notice-error unsplash-error is-dismissible'
		);
		this.views.add( this.attachmentsError );
	},

	updateContent() {
		const view = this;
		const noItemsView = view.attachmentsNoResults;

		if ( ! this.collection.length ) {
			this.toolbar.get( 'spinner' ).show();
			this.dfd = this.collection.more().done( function() {
				if ( ! view.collection.length ) {
					noItemsView.$el.removeClass( 'hidden' );
				} else {
					noItemsView.$el.addClass( 'hidden' );
				}
				view.toolbar.get( 'spinner' ).hide();
				view.showError();
			} );
		} else {
			noItemsView.$el.addClass( 'hidden' );
			view.toolbar.get( 'spinner' ).hide();
			view.showError();
		}
	},
	showError() {
		const errorView = this.attachmentsError;
		const toolbarView = this.toolbar;
		if (
			! this.collection.respSuccess() &&
			this.collection.respErrorMessage()
		) {
			const error = this.collection.respErrorMessage();
			errorView.$el.html( error.message );
			errorView.$el.removeClass( 'hidden' );
			toolbarView.$el.addClass( 'hidden' );
		}
	},
	focusInput() {
		if (
			this.searchFilter &&
			this.searchFilter.$el &&
			! this.searchFilter.$el.val()
		) {
			this.searchFilter.$el.focus();
		}
	},
} );

export default ImagesBrowser;
