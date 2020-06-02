/**
 * Internal dependencies
 */
import getConfig from '../../../../assets/src/media-selector/helpers/get-config';

describe( 'get-config', () => {
	beforeAll( () => {
		// Delete the existing
		delete window.unsplash;
		window.unsplash = {
			test: '123',
		};
	} );

	afterAll( () => {
		// Delete the existing
		delete window.unsplash;
	} );

	it( 'check key', () => {
		expect( getConfig( 'test' ) ).toBe( '123' );
	} );

	it( 'invalid key', () => {
		expect( getConfig( 'wordpress' ) ).toBeUndefined();
	} );
} );
