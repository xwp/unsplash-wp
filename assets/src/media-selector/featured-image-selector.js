/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import UnsplashMediaUpload from './components/unsplash-media-upload.js';

/**
 * We can't override the featured image media frame Gutenberg by extending Backbone views, meaning we can't initialize
 * our own state to load the Unsplash tab. Instead, we have to filter the `editor.MediaUpload` component
 * (which is used by `editor.PostFeaturedImage`) and extend it to initialize our state.
 */
addFilter(
	'editor.MediaUpload',
	'unsplash/extend-featured-image',
	() => UnsplashMediaUpload
);
