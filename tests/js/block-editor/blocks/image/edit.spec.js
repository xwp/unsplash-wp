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
import Edit, {
	pickRelevantImageProps,
	getFilename,
} from '../../../../../assets/src/block-editor/blocks/image/edit';

// Mock the <InspectorControls> component only, so that the other components in this package behave as usual.
jest.mock( '@wordpress/block-editor', () => {
	const original = require.requireActual( '@wordpress/block-editor' );
	return {
		...original,
		InspectorControls: ( { children } ) => children,
	};
} );

const image = {
	id: 2,
	alt: 'Example',
	link: 'http://example.com/image',
	caption: 'Example Image',
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
				source_url: 'https://images.unsplash.com/example-photo-300x240.png',
			},
			thumbnail: {
				file: 'example-photo-150x150.png',
				width: 150,
				height: 150,
				mime_type: 'image/png',
				source_url: 'https://images.unsplash.com/example-photo-150x150.png',
			},
			large: {
				file: 'example-photo-large.png',
				width: 587,
				height: 469,
				mime_type: 'image/png',
				source_url: 'https://images.unsplash.com/example-photo-large.png',
			},
			full: {
				file: 'example-photo.png',
				width: 1000,
				height: 1000,
				mime_type: 'image/png',
				source_url: 'https://images.unsplash.com/example-photo',
			},
		},
	},
};

registerStore( 'core', {
	reducer: jest.fn(),
	selectors: {
		getMedia: () => {
			return image;
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

	it( '`pickRelevantImageProps` picks the relevant props', () => {
		const props = pickRelevantImageProps( image );

		expect( Object.keys( props ) ).toHaveLength( 5 );
		expect( props.id ).toStrictEqual( 2 );
		expect( props.alt ).toStrictEqual( 'Example' );
		expect( props.caption ).toStrictEqual( 'Example Image' );
		expect( props.link ).toStrictEqual( 'http://example.com/image' );
		expect( props.url ).toStrictEqual(
			'https://images.unsplash.com/example-photo-large.png'
		);
	} );

	it( '`getFilename` picks the relevant props', () => {
		const name = getFilename(
			'https://images.unsplash.com/example-photo-large.png'
		);

		expect( name ).toStrictEqual( 'example-photo-large.png' );
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
