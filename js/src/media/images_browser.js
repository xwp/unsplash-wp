/**
 * Internal dependencies
 */
import OrderByFilter from './filters/order_by_filter';
import SearchFilter from './filters/search_filter';

const ImagesBrowser = wp.media.view.AttachmentsBrowser.extend( {
	className: 'unsplash-browser attachments-browser',

	createToolbar() {
		const { toolbar } = window.unsplash;

		this.toolbar = new wp.media.view.Toolbar( {
			controller: this.controller,
		} );

		this.views.add( this.toolbar );

		this.toolbar.set( 'spinner', new wp.media.view.Spinner( {
			priority: -20,
		} ) );

		// Create a heading before the select elements that filters the images.
		this.toolbar.set( 'filtersHeading', new wp.media.view.Heading( {
			priority: -100,
			text: toolbar.heading,
			level: 'h2',
			className: 'media-attachments-filter-heading',
		} ).render() );

		// Label for the 'order by' filter. This is a visually hidden element and needs to be rendered before.
		this.toolbar.set( 'orderByFilterLabel', new wp.media.view.Label( {
			value: toolbar.filters.orderBy.label,
			attributes: {
				for: 'media-attachment-filters',
			},
			priority: -80,
		} ).render() );

		// Create 'order by' filter.
		this.toolbar.set( 'orderByFilter', new OrderByFilter( {
			controller: this.controller,
			model: this.collection.props,
			priority: -80,
		} ).render() );

		// Label for the 'search' filter. This is a visually hidden element and needs to be rendered before.
		this.toolbar.set( 'searchLabel', new wp.media.view.Label( {
			value: toolbar.filters.search.label,
			className: 'media-search-input-label',
			attributes: {
				for: 'media-search-input',
			},
			priority: 60,
		} ).render() );

		// Create search filter.
		this.toolbar.set( 'searchFilter', new SearchFilter( {
			controller: this.controller,
			model: this.collection.props,
			priority: 60,
		} ).render() );
	},
} );

export default ImagesBrowser;
