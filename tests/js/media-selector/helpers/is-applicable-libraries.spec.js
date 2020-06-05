/**
 * Internal dependencies
 */
import { isApplicableLibraries } from '../../../../assets/src/media-selector/helpers';

describe( 'is-applicable-libraries', () => {
	it( 'check string of video', () => {
		expect( isApplicableLibraries( 'video' ) ).toBe( false );
	} );

	it( 'check string of library', () => {
		expect( isApplicableLibraries( 'library' ) ).toBe( true );
	} );
} );
