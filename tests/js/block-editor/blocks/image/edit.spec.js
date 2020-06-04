/**
 * External dependencies
 */
import '@testing-library/jest-dom/extend-expect';
import { render, screen, fireEvent } from '@testing-library/react';
import { cloneDeep } from 'lodash';

/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';

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
		__experimentalBlock: {
			figure: ( { children } ) => <figure>{ children }</figure>,
		},
		InspectorControls: ( { children } ) => children,
		InspectorAdvancedControls: ( { children } ) => children,
	};
} );

jest.mock( '@wordpress/compose', () => {
	const original = require.requireActual( '@wordpress/compose' );
	return {
		...original,
		useViewportMatch: () => true,
	};
} );

jest.mock( '@wordpress/block-library/build/image/image-size', () => ( {
	__esModule: true,
	default: () => ( {
		imageWidth: 1024,
		imageHeight: 768,
		imageWidthWithinContainer: 1024,
		imageHeightWithinContainer: 768,
	} ),
} ) );

jest.mock( '@wordpress/data', () => {
	const imageMock = {
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

	const selectFn = store => {
		switch ( store ) {
			case 'core':
				return {
					getMedia: () => {
						return imageMock;
					},
				};

			case 'core/block-editor':
				return {
					getSettings: () => ( {
						mediaUpload: jest.fn(),
						imageSizes: [
							{ slug: 'thumbnail', name: 'Thumbnail' },
							{ slug: 'medium', name: 'Medium' },
							{ slug: 'large', name: 'Large' },
							{ slug: 'full', name: 'Full Size' },
						],
						isRTL: false,
						maxWidth: 580,
					} ),
				};

			default:
				return {};
		}
	};

	const dispatchFn = store => {
		switch ( store ) {
			case 'core/block-editor':
				return {
					toggleSelection: jest.fn(),
				};

			default:
				return {};
		}
	};

	return {
		useSelect: jest.fn( mapSelect => {
			return mapSelect( selectFn );
		} ),
		useDispatch: jest.fn( storeName => dispatchFn( storeName ) ),
		select: selectFn,
		dispatch: dispatchFn,
	};
} );

const image = select( 'core' ).getMedia();

const baseProps = {
	attributes: {
		url: 'https://images.unsplash.com/example-photo',
		alt: 'Example Photo',
		caption:
			'Photo by <a href="https://unsplash.com/@user" rel="nofollow">User</a> on <a href="https://unsplash.com" rel="nofollow">Unsplash</a>',
		align: 'center',
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
	const eventListeners = {};
	function Unsplash() {
		return {
			open: jest.fn(),
			on: jest.fn( ( event, callback ) => {
				eventListeners[ event ] = eventListeners[ event ] || [];
				eventListeners[ event ].push( callback );
			} ),
			state: () => ( {
				get: () => ( {
					toJSON: () => [
						{
							id: 2,
							unsplash_order: 1,
							title: '',
							filename: 'example-photo.jpeg',
							url: 'https://images.unsplash.com/example-photo',
							link: 'https://unsplash.com',
							alt: '',
							author: 'Example Author',
							description: '',
							caption:
								'Photo by <a href="https://unsplash.com/@user" rel="nofollow">User</a> on <a href="https://unsplash.com" rel="nofollow">Unsplash</a> ',
							color: '#F4F7F9',
							name: 'example',
							height: 6000,
							width: 4000,
							status: 'inherit',
							uploadedTo: 0,
							date: '2020-06-04T06:53:22.000Z',
							modified: '2020-06-04T14:00:02.000Z',
							menuOrder: 0,
							mime: 'image/jpeg',
							type: 'image',
							subtype: 'jpeg',
							icon:
								'https://images.unsplash.com/example-photo?ixlib=rb-1.2.1&q=85&fm=jpg&crop=entropy&cs=tinysrgb&w=150&fit=crop&ixid=eyJhcHBfaWQiOjEzMjc4NX0&h=150',
							dateFormatted: 'June 4, 2020',
							nonces: {
								update: 'NONCE',
								delete: 'NONCE',
								edit: 'NONCE',
							},
							editLink: false,
							meta: false,
							sizes: {
								full: {
									url: 'https://images.unsplash.com/example-photo',
									height: 6000,
									width: 4000,
									orientation: 0,
								},
								medium: {
									url:
										'https://images.unsplash.com/example-photo?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEzMjc4NX0&fm=jpg&q=85&fit=crop&w=400&h=600',
									height: 600,
									width: 400,
									orientation: 0,
								},
								thumbnail: {
									url:
										'https://images.unsplash.com/example-photo?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEzMjc4NX0&fm=jpg&q=85&fit=crop&w=150&h=150',
									height: 150,
									width: 150,
									orientation: 0,
								},
								medium_large: {
									url:
										'https://images.unsplash.com/example-photo?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEzMjc4NX0&fm=jpg&q=85&fit=crop&w=768&h=1152',
									height: 1152,
									width: 768,
									orientation: 0,
								},
								large: {
									url:
										'https://images.unsplash.com/example-photo?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEzMjc4NX0&fm=jpg&q=85&fit=crop&w=1024&h=1024',
									height: 1024,
									width: 1024,
									orientation: 0,
								},
								'1536x1536': {
									url:
										'https://images.unsplash.com/example-photo?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEzMjc4NX0&fm=jpg&q=85&fit=crop&w=1536&h=1536',
									height: 1536,
									width: 1536,
									orientation: 0,
								},
								'2048x2048': {
									url:
										'https://images.unsplash.com/example-photo?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEzMjc4NX0&fm=jpg&q=85&fit=crop&w=2048&h=2048',
									height: 2048,
									width: 2048,
									orientation: 0,
								},
								'post-thumbnail': {
									url:
										'https://images.unsplash.com/example-photo?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEzMjc4NX0&fm=jpg&q=85&fit=crop&w=1200&h=1800',
									height: 1800,
									width: 1200,
									orientation: 0,
								},
							},
						},
					],
				} ),
			} ),
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

	beforeEach( () => {
		window.getSelection = () => {
			return {
				addRange: () => {},
				removeAllRanges: () => {},
			};
		};

		document.createRange = () => ( {
			setStart: () => {},
			setEnd: () => {},
			commonAncestorContainer: {
				nodeName: 'BODY',
				ownerDocument: document,
			},
		} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
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

	it( 'matches snapshot when an image is right aligned', () => {
		const props = cloneDeep( baseProps );
		props.attributes.align = 'right';
		const wrapper = setup( props );
		expect( wrapper ).toMatchSnapshot();
	} );

	it( 'matches snapshot when an image is left aligned', () => {
		const props = cloneDeep( baseProps );
		props.attributes.align = 'left';
		const wrapper = setup( props );
		expect( wrapper ).toMatchSnapshot();
	} );

	it( 'matches snapshot when an image is wide aligned', () => {
		const props = cloneDeep( baseProps );
		props.attributes.align = 'wide';
		const wrapper = setup( props );
		expect( wrapper ).toMatchSnapshot();
	} );

	it( 'matches snapshots if no alt', () => {
		const props = cloneDeep( baseProps );
		props.attributes.alt = '';
		let wrapper = setup( props );
		expect( wrapper ).toMatchSnapshot();

		props.attributes.url = '';
		wrapper = setup( props );
		expect( wrapper ).toMatchSnapshot();
	} );

	it( 'displays `Image settings` panel', () => {
		setup( baseProps );
		expect( screen.getByText( 'Image settings' ) ).toBeInTheDocument();
	} );

	it( 'updates alt text', () => {
		setup( baseProps );

		fireEvent.change( screen.getByLabelText( 'Alt text (alternative text)' ), {
			target: { value: 'Updated alt text' },
		} );

		expect( baseProps.setAttributes ).toHaveBeenCalledWith( {
			alt: 'Updated alt text',
		} );
	} );

	it( 'updates image sizeSlug', () => {
		setup( baseProps );

		fireEvent.change( screen.getByLabelText( 'Image size' ), {
			target: { value: 'medium' },
		} );

		expect( baseProps.setAttributes ).toHaveBeenCalledWith( {
			sizeSlug: 'medium',
			height: undefined,
			width: undefined,
			url: 'https://images.unsplash.com/example-photo-300x240.png',
		} );
	} );

	it( 'updates title attribute', () => {
		setup( baseProps );

		fireEvent.change( screen.getByLabelText( 'Title attribute' ), {
			target: { value: 'Updated title' },
		} );

		expect( baseProps.setAttributes ).toHaveBeenCalledWith( {
			title: 'Updated title',
		} );
	} );

	it( 'updates width and height', () => {
		setup( baseProps );

		fireEvent.change( screen.getByLabelText( 'Width' ), {
			target: { value: 600 },
		} );

		fireEvent.change( screen.getByLabelText( 'Height' ), {
			target: { value: 400 },
		} );

		expect( baseProps.setAttributes.mock.calls[ 0 ][ 0 ] ).toStrictEqual( {
			width: 600,
		} );

		expect( baseProps.setAttributes.mock.calls[ 1 ][ 0 ] ).toStrictEqual( {
			height: 400,
		} );
	} );

	it( 'updates width and height when size controls are used', () => {
		setup( baseProps );

		fireEvent.click( screen.getByText( '25%' ) );
		expect( baseProps.setAttributes.mock.calls[ 0 ][ 0 ] ).toStrictEqual( {
			width: 256,
			height: 192,
		} );

		fireEvent.click( screen.getByText( '75%' ) );
		expect( baseProps.setAttributes.mock.calls[ 1 ][ 0 ] ).toStrictEqual( {
			width: 768,
			height: 576,
		} );
	} );

	it( 'updates caption', () => {
		setup( baseProps );

		const caption = screen.getByLabelText( 'Write caption…' );

		fireEvent.click( caption );
		caption.textContent = 'Updated caption';
		fireEvent.input( caption );

		expect( baseProps.setAttributes ).toHaveBeenCalledWith( {
			caption: 'Updated caption',
		} );
	} );

	it( 'invokes `onSelect` and `setAttributes` when an image is selected', () => {
		setup( baseProps );

		eventListeners.select.forEach( cb => cb() );
		expect( baseProps.setAttributes.mock.calls[ 0 ][ 0 ] ).toStrictEqual( {
			alt: '',
			caption:
				'Photo by <a href="https://unsplash.com/@user" rel="nofollow">User</a> on <a href="https://unsplash.com" rel="nofollow">Unsplash</a> ',
			height: undefined,
			href: 'https://unsplash.com',
			id: 2,
			link: 'https://unsplash.com',
			sizeSlug: 'large',
			unsplashId: undefined,
			url:
				'https://images.unsplash.com/example-photo?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEzMjc4NX0&fm=jpg&q=85&fit=crop&w=1024&h=1024',
			width: undefined,
		} );
	} );
} );
