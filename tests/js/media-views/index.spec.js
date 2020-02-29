import { checkType } from '../../../assets/src/media-views';

describe( 'media-views', () => {
	describe( 'SelectUnsplash', () => {
		it( 'should do something', () => {
			expect( true ).toBe( true );
		} );
	} );

	describe( 'QueryUnsplash', () => {
		it( 'should do something', () => {
			expect( true ).toBe( true );
		} );
	} );

	describe( 'checkType', () => {
		it( 'should not return true', () => {
			expect( checkType( [] ) ).toStrictEqual( false );
		} );

		it( 'should return true', () => {
			expect( checkType( [ 'image' ] ) ).toStrictEqual( true );
		} );
	} );
} );
