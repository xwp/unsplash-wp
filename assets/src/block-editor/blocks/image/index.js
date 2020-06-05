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
	icon,
	keywords: [
		'img', // "img" is not translated as it is intended to reflect the HTML <img> tag.
		'unsplash', // not translated as this is intended to be an exact keyword.
		__( 'photo', 'unsplash' ),
		__( 'image', 'unsplash' ),
	],
	example: {
		attributes: {
			sizeSlug: 'large',
			url:
				'https://images.unsplash.com/photo-1591213775120-febdfca65523?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=500&q=80',
			caption:
				'Photo by <a href="https://unsplash.com/@timb_motivv" rel="nofollow">Tim Motivv</a> on <a href="https://unsplash.com/?utm_source=unsplash&#038;utm_medium=referral" rel="nofollow">Unsplash</a> ',
		},
	},
	styles: [
		{
			name: 'default',
			label: _x( 'Default', 'block style', 'unsplash' ),
			isDefault: true,
		},
		{ name: 'rounded', label: _x( 'Rounded', 'block style', 'unsplash' ) },
	],
	transforms,
	edit,
	save,
};
