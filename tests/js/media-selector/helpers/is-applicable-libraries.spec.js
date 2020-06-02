/**
 * Internal dependencies
 */
import checkLibrary from '../../../../assets/src/media-selector/helpers/is-applicable-libraries';

describe( 'is-applicable-libraries', () => {
	it( 'check string of video', () => {
		expect( checkLibrary( 'video' ) ).toBe( false );
	} );

	it( 'check string of library', () => {
		expect( checkLibrary( 'library' ) ).toBe( true );
	} );
} );
