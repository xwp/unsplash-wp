/**
 * WordPress dependencies
 */
import { __, _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import icon from './icon';
import transforms from './transforms';
import edit from './edit';
import save from '@wordpress/block-library/build/image/save';

export const name = 'unsplash/image';

export const settings = {
	title: __( 'Unsplash', 'unsplash' ),
	description: __(
		"Search and select from the internet's source of freely usable images.",
		'unsplash'
	),
	category: 'common',
	icon,
	keywords: [
		'img', // "img" is not translated as it is intended to reflect the HTML <img> tag.
		__( 'photo', 'unsplash' ),
		__( 'image', 'unsplash' ),
	],
	supports: {
		lightBlockWrapper: true,
		align: true,
	},
	styles: [
		{
			name: 'default',
			label: _x( 'Default', 'block style', 'unsplash' ),
			isDefault: true,
		},
		{ name: 'rounded', label: _x( 'Rounded', 'block style', 'unsplash' ) },
	],
	attributes: {
		align: {
			type: 'string',
		},
		url: {
			type: 'string',
			source: 'attribute',
			selector: 'img',
			attribute: 'src',
		},
		alt: {
			type: 'string',
			source: 'attribute',
			selector: 'img',
			attribute: 'alt',
			default: '',
		},
		caption: {
			type: 'string',
			source: 'html',
			selector: 'figcaption',
		},
		title: {
			type: 'string',
			source: 'attribute',
			selector: 'img',
			attribute: 'title',
		},
		href: {
			type: 'string',
			source: 'attribute',
			selector: 'figure > a',
			attribute: 'href',
		},
		rel: {
			type: 'string',
			source: 'attribute',
			selector: 'figure > a',
			attribute: 'rel',
		},
		linkClass: {
			type: 'string',
			source: 'attribute',
			selector: 'figure > a',
			attribute: 'class',
		},
		id: {
			type: 'number',
		},
		width: {
			type: 'number',
		},
		height: {
			type: 'number',
		},
		sizeSlug: {
			type: 'string',
		},
		linkDestination: {
			type: 'string',
			default: 'none',
		},
		linkTarget: {
			type: 'string',
			source: 'attribute',
			selector: 'figure > a',
			attribute: 'target',
		},
	},
	transforms,
	edit,
	save,
};
