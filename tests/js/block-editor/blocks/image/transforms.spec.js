/**
 * Internal dependencies
 */
import transforms from '../../../../../assets/src/block-editor/blocks/image/transforms';

jest.mock( '@wordpress/blocks', () => {
	return {
		createBlock: jest.fn( () => ( { name: 'core/image' } ) ),
	};
} );

describe( 'blocks: unsplash/image: transforms', () => {
	describe( 'transforms', () => {
		it( 'should equal transforms object `to` property', () => {
			const to = transforms.to.pop();

			expect( to.type ).toStrictEqual( 'block' );
			expect( to.blocks ).toContain( 'core/image' );
			expect( to.transform() ).toStrictEqual( {
				name: 'core/image',
			} );
		} );
	} );
} );
