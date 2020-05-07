/**
 * WordPress dependencies
 */
import { createNewPost, insertBlock } from '@wordpress/e2e-test-utils';
import { clickButton } from '../../utils';

describe( 'Image Block', () => {
	beforeEach( async () => {
		await createNewPost( {} );
	} );

	it( 'should the tab exist', async () => {
		const MEDIA_LIBRARY_BUTTON = '.wp-block-image .components-button';
		const UNSPLASH_LIBRARY_BUTTON = '#menu-item-unsplash';

		// Insert image block.
		await insertBlock( 'Image' );

		// Click the media library button and wait for tab.
		await page.waitForSelector( MEDIA_LIBRARY_BUTTON );
		await page.click( MEDIA_LIBRARY_BUTTON );
		await clickButton( 'Library' );
		await page.waitForSelector( '.media-modal' );
		await page.waitForSelector( UNSPLASH_LIBRARY_BUTTON );
		await expect( page ).toMatchElement( UNSPLASH_LIBRARY_BUTTON );
	} );
} );
