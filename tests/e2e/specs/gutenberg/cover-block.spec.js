/**
 * WordPress dependencies
 */
import { createNewPost, insertBlock } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { clickButton, deactivatePlugin } from '../../utils';
import { UNSPLASH_LIBRARY_BUTTON, UNSPLASH_MODAL } from '../../constants';

describe( 'Cover Block', () => {
	beforeAll( async () => {
		await deactivatePlugin( 'classic-editor' );
	} );

	beforeEach( async () => {
		await createNewPost( {} );
	} );

	it( 'should the tab exist', async () => {
		// Insert cover block.
		await insertBlock( 'Cover' );

		// Click the media library button and wait for tab.
		await clickButton( 'Media Library' );
		await page.waitForSelector( UNSPLASH_MODAL, {
			visible: true,
		} );
		await page.waitForSelector( UNSPLASH_LIBRARY_BUTTON );
		await expect( page ).toMatchElement( UNSPLASH_LIBRARY_BUTTON );
	} );
} );
