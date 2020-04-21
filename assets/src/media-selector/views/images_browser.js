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
		this.collection.on( 'add remove reset', this.updateLayout, this );
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
		this.attachmentsNoResults.$el.html(
			`<img src="${ noResults.image }" alt="${ noResults.noMedia }"/>`
		);
		this.attachmentsNoResults.$el.append( `<p>${ noResults.noMedia }</p>` );

		this.views.add( this.attachmentsNoResults );
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
			} );
		} else {
			noItemsView.$el.addClass( 'hidden' );
			view.toolbar.get( 'spinner' ).hide();
		}
	},

	updateLayout() {
		this.attachments.setupMacy();
		this.attachments.refreshMacy();
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
