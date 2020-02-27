/**
 * Internal dependencies
 */
import SearchFilter from './filters/search_filter';

const ImagesBrowser = wp.media.view.AttachmentsBrowser.extend( {
	className: 'unsplash-browser attachments-browser',

	createToolbar() {
		const { toolbar } = window.unsplash;

		this.toolbar = new wp.media.view.Toolbar( {
			controller: this.controller,
		} );

		this.views.add( this.toolbar );

		// TODO: replace with better spinner.
		this.toolbar.set( 'spinner', new wp.media.view.Spinner( {
			priority: -20,
		} ) );

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
