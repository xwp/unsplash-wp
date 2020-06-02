/**
 * Internal dependencies
 */
import checkType from '../../../../assets/src/media-selector/helpers/is-image-included';

describe( 'is-image-included', () => {
	it( 'check empty array', () => {
		expect( checkType( [] ) ).toBe( false );
	} );

	it( 'check with video array', () => {
		expect( checkType( [ 'video' ] ) ).toBe( false );
	} );

	it( 'check array with image', () => {
		expect( checkType( [ 'image' ] ) ).toBe( true );
	} );

	it( 'check string of image', () => {
		expect( checkType( 'image' ) ).toBe( true );
	} );
} );
