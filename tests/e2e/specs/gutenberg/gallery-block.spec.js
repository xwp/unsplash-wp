/**
 * WordPress dependencies
 */
import { createNewPost, insertBlock } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { clickButton, deactivatePlugin } from '../../utils';
import { UNSPLASH_LIBRARY_BUTTON, UNSPLASH_MODAL } from '../../constants';

describe( 'Image Block', () => {
	beforeAll( async () => {
		await deactivatePlugin( 'classic-editor' );
	} );

	beforeEach( async () => {
		await createNewPost( {} );

		// Insert image block.
		await insertBlock( 'Gallery' );

		// Click the media library button and wait for tab.
		await clickButton( 'Media Library' );
		await page.waitForSelector( UNSPLASH_MODAL );
	} );

	it( 'should the tab not exist', async () => {
		await expect( page ).not.toMatchElement( UNSPLASH_LIBRARY_BUTTON );
	} );
} );
