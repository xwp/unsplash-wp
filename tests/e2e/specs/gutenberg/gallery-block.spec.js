/**
 * WordPress dependencies
 */
import { createNewPost, insertBlock } from '@wordpress/e2e-test-utils';
import { clickButton } from '../../utils';

const UNSPLASH_LIBRARY_BUTTON = '#menu-item-unsplash';

describe( 'Image Block', () => {
	beforeEach( async () => {
		await createNewPost( {} );

		// Insert image block.
		await insertBlock( 'Gallery' );

		// Click the media library button and wait for tab.
		await clickButton( 'Media Library' );
		await page.waitForSelector( '.media-modal' );
	} );

	it( 'should the tab not exist', async () => {
		await expect( page ).not.toMatchElement( UNSPLASH_LIBRARY_BUTTON );
	} );
} );
