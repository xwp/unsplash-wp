/**
 * Internal dependencies
 */
import { isImageIncluded } from '../../../../assets/src/media-selector/helpers';

describe( 'is-image-included', () => {
	it( 'check empty array', () => {
		expect( isImageIncluded( [] ) ).toBe( false );
	} );

	it( 'check with video array', () => {
		expect( isImageIncluded( [ 'video' ] ) ).toBe( false );
	} );

	it( 'check array with image', () => {
		expect( isImageIncluded( [ 'image' ] ) ).toBe( true );
	} );

	it( 'check string of image', () => {
		expect( isImageIncluded( 'image' ) ).toBe( true );
	} );
} );
