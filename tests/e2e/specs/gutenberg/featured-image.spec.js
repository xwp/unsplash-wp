/**
 * WordPress dependencies
 */
import { createNewPost } from '@wordpress/e2e-test-utils';
import { clickButton } from '../../utils';

/**
 * Tests the notices for the featured image.
 */
describe( 'Featured Image', () => {
	beforeEach( async () => {
		await createNewPost();
		await clickButton( 'Document' );
		await clickButton( 'Featured image' );
		await clickButton( 'Set featured image' );
	} );

	it( 'should the tab exist', async () => {
		const UNSPLASH_LIBRARY_BUTTON = '#menu-item-unsplash';
		await page.waitForSelector( '.media-modal' );
		await page.waitForSelector( UNSPLASH_LIBRARY_BUTTON );
		await expect( page ).toMatchElement( UNSPLASH_LIBRARY_BUTTON );
	} );
} );
