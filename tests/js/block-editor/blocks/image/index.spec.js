/**
 * Internal dependencies
 */
import {
	name,
	settings,
} from '../../../../../assets/src/block-editor/blocks/image';
import transforms from '../../../../../assets/src/block-editor/blocks/image/transforms';
import Edit from '../../../../../assets/src/block-editor/blocks/image/edit';
import Save from '@wordpress/block-library/build/image/save';

describe( 'blocks: unsplash/image', () => {
	describe( 'name', () => {
		it( 'should equal unsplash/image', () => {
			expect( name ).toStrictEqual( 'unsplash/image' );
		} );
	} );

	describe( 'title settings', () => {
		it( 'should equal Unsplash', () => {
			expect( settings.title ).toStrictEqual( 'Unsplash' );
		} );
	} );

	describe( 'description settings', () => {
		it( "should equal `The internet's source of freely usable images.`", () => {
			expect( settings.description ).toStrictEqual(
				"Search and select from the internet's source of freely usable images."
			);
		} );
	} );

	describe( 'category settings', () => {
		it( 'should equal common', () => {
			expect( settings.category ).toStrictEqual( 'common' );
		} );
	} );

	describe( 'supports settings', () => {
		it( 'should have lightBlockWrapper and align support', () => {
			expect( settings.supports ).toStrictEqual( {
				lightBlockWrapper: true,
				align: true,
			} );
		} );
	} );

	describe( 'styles settings', () => {
		it( 'should have default and rounded styles', () => {
			expect( settings.styles.map( style => style.name ) ).toStrictEqual( [
				'default',
				'rounded',
			] );
		} );
	} );

	describe( 'attributes', () => {
		it( 'should be a structured object', () => {
			expect( settings.attributes ).toStrictEqual( {
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
			} );
		} );
	} );

	describe( 'settings transforms property', () => {
		it( 'should be equal to the transforms object', () => {
			expect( settings.transforms ).toStrictEqual( transforms );
		} );
	} );

	describe( 'settings edit property', () => {
		it( 'should be equal to the Edit component', () => {
			expect( settings.edit ).toStrictEqual( Edit );
		} );
	} );

	describe( 'settings save property', () => {
		it( 'should be equal to the Save component', () => {
			expect( settings.save ).toStrictEqual( Save );
		} );
	} );
} );
