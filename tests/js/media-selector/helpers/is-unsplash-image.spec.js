/**
 * Internal dependencies
 */
import { isUnsplashImage } from '../../../../assets/src/media-selector/helpers';

describe( 'is-unsplash-image', () => {
	it( 'valid attachment', () => {
		const attachment = {
			attributes: {
				unsplash_order: 2,
				id: 'fsfds',
			},
		};
		expect( isUnsplashImage( attachment ) ).toBe( true );
	} );
	it( 'invalid attachment', () => {
		const attachment = {
			attributes: {
				unsplash_order: 2,
				id: 22,
			},
		};
		expect( isUnsplashImage( attachment ) ).toBe( false );
	} );
	it( 'no order attachment', () => {
		const attachment = {
			attributes: {
				id: 22,
			},
		};
		expect( isUnsplashImage( attachment ) ).toBe( false );
	} );
} );
