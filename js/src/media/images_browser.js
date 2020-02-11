/**
 * Internal dependencies
 */
import ImageFilters from './filters/order_by_filter';

const ImagesBrowser = wp.media.view.AttachmentsBrowser.extend( {
	className: 'unsplash-browser attachments-browser',

	createToolbar() {
		this.toolbar = new wp.media.view.Toolbar( {
			controller: this.controller,
		} );

		this.views.add( this.toolbar );

		this.toolbar.set( 'spinner', new wp.media.view.Spinner( {
			priority: -20,
		} ) );

		/*
		 * Create a h2 heading before the select elements that filter attachments.
		 * This heading is visible in the modal and visually hidden in the grid.
		 */
		this.toolbar.set( 'filters-heading', new wp.media.view.Heading( {
			priority: -100,
			text: 'Filter images',
			level: 'h2',
			className: 'media-attachments-filter-heading',
		} ).render() );

		// "Filters" is a <select>, a visually hidden label element needs to be rendered before.
		this.toolbar.set( 'filtersLabel', new wp.media.view.Label( {
			value: 'Filter by type',
			attributes: {
				for: 'media-attachment-filters',
			},
			priority: -80,
		} ).render() );

		const filters = new ImageFilters( {
			controller: this.controller,
			model: this.collection.props,
			priority: -80,
		} );

		this.toolbar.set( 'filters', filters.render() );

		// Search is an input, a visually hidden label element needs to be rendered before.
		this.toolbar.set( 'searchLabel', new wp.media.view.Label( {
			value: 'Search',
			className: 'media-search-input-label',
			attributes: {
				for: 'media-search-input',
			},
			priority: 60,
		} ).render() );
		this.toolbar.set( 'search', new wp.media.view.Search( {
			controller: this.controller,
			model: this.collection.props,
			priority: 60,
		} ).render() );
	},
} );

export default ImagesBrowser;
