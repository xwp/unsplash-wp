/**
 * WordPress dependencies
 */
import { createNewPost, insertBlock } from '@wordpress/e2e-test-utils';
import { clickButton } from '../../utils';

const MEDIA_LIBRARY_BUTTON = '.wp-block-image .components-button';
const UNSPLASH_LIBRARY_BUTTON = '#menu-item-unsplash';

describe( 'Image Block', () => {
	beforeEach( async () => {
		await createNewPost( {} );

		// Insert image block.
		await insertBlock( 'Image' );

		// Click the media library button and wait for tab.
		await page.waitForSelector( MEDIA_LIBRARY_BUTTON );
		await page.click( MEDIA_LIBRARY_BUTTON );
		await clickButton( 'Library' );
		await page.waitForSelector( '.media-modal' );
		await page.waitForSelector( UNSPLASH_LIBRARY_BUTTON );
	} );

	it( 'should the tab exist', async () => {
		await expect( page ).toMatchElement( UNSPLASH_LIBRARY_BUTTON );
	} );

	it( 'Search: results found', async () => {
		await page.waitForSelector( '#unsplash-search-input' );
		await page.keyboard.type( 'WordPress' );

		attachments
	} );

} );
