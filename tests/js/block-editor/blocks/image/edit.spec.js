/**
 * External dependencies
 */
import '@testing-library/jest-dom/extend-expect';
import { render } from '@testing-library/react';
import { cloneDeep } from 'lodash';

/**
 * WordPress dependencies
 */
import { registerStore } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Edit from '../../../../../assets/src/block-editor/blocks/image/edit';

// Mock the <InspectorControls> component only, so that the other components in this package behave as usual.
jest.mock( '@wordpress/block-editor', () => {
	const original = require.requireActual( '@wordpress/block-editor' );
	return {
		...original,
		InspectorControls: ( { children } ) => children,
	};
} );

registerStore( 'core', {
	reducer: jest.fn(),
	selectors: {
		getMedia: () => {
			return {
				id: 2,
				media_details: {
					width: 1000,
					height: 1000,
					file: 'example-photo.png',
					sizes: {
						medium: {
							file: 'example-photo-300x240.png',
							width: 300,
							height: 240,
							mime_type: 'image/png',
							source_url:
								'https://images.unsplash.com/example-photo-300x240.png',
						},
						thumbnail: {
							file: 'example-photo-150x150.png',
							width: 150,
							height: 150,
							mime_type: 'image/png',
							source_url:
								'https://images.unsplash.com/example-photo-150x150.png',
						},
						full: {
							file: 'example-photo.png',
							width: 587,
							height: 469,
							mime_type: 'image/png',
							source_url: 'https://images.unsplash.com/example-photo',
						},
					},
				},
			};
		},
	},
} );

const baseProps = {
	attributes: {
		url: 'https://images.unsplash.com/example-photo',
		alt: 'Example Photo',
		caption:
			'Photo by <a href="https://unsplash.com/@user" rel="nofollow">User</a> on <a href="https://unsplash.com" rel="nofollow">Unsplash</a>',
		align: 'wide',
		id: 2,
		href: 'https://unsplash.com',
		linkClass: 'url-class',
		linkDestination: 'attachment',
		sizeSlug: 'large',
	},
	setAttributes: jest.fn(),
	className: 'is-style-default',
	isSelected: true,
};

const setup = props => {
	return render( <Edit { ...props } /> );
};

describe( 'blocks: unspash/image: edit', () => {
	function Unsplash() {
		return {
			open: jest.fn(),
			on: jest.fn(),
		};
	}

	beforeAll( () => {
		global.wp = {
			media: {
				view: {
					MediaFrame: {
						Unsplash,
					},
				},
			},
		};
	} );

	it( 'matches snapshot when no image is selected', () => {
		const props = cloneDeep( baseProps );
		props.attributes.url = '';
		props.attributes.id = '';
		const wrapper = setup( props );
		expect( wrapper ).toMatchSnapshot();
	} );

	it( 'matches snapshot when an image is selected', () => {
		const wrapper = setup( baseProps );
		expect( wrapper ).toMatchSnapshot();
	} );
} );
