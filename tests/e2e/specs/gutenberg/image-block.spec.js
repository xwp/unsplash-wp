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
		await insertBlock( 'Image' );

		// Click the media library button and wait for tab.
		await clickButton( 'Media Library' );
		await page.waitForSelector( '.media-modal' );
		await page.waitForSelector( UNSPLASH_LIBRARY_BUTTON );
	} );

	it( 'should the tab exist', async () => {
		await expect( page ).toMatchElement( UNSPLASH_LIBRARY_BUTTON );
	} );

	it( 'Search: results found', async () => {
		await page.waitForSelector( '#unsplash-search-input' );
		await page.keyboard.type( 'WordPress' );
		const CONTAINER = '.unsplash-browser .attachments';
		await page.waitForSelector( CONTAINER );
		await expect( page ).toMatchElement( CONTAINER );
	} );

	it( 'Search: no results found', async () => {
		await page.waitForSelector( '#unsplash-search-input' );
		await page.keyboard.type( 'dsfdsfs' );
		const NO_RESULTS = '.unsplash-browser .show';
		await page.waitForSelector( NO_RESULTS );
		await expect( page ).toMatchElement( NO_RESULTS );
	} );

	it( 'insert image', async () => {
		const CONTAINER = '.unsplash-browser .attachments';
		await page.waitForSelector( CONTAINER );
		const btnSelector =
			'.unsplash-browser .attachments .unsplash-attachment:first-of-type';
		await page.waitForSelector( btnSelector );
		await page.evaluate( selector => {
			document.querySelector( selector ).click();
		}, btnSelector );
		const btnSelect = '.media-button-select';
		await page.evaluate( selector => {
			document.querySelector( selector ).click();
		}, btnSelect );
		const blockClass = '.wp-block-image';
		await page.waitForSelector( blockClass );
		await expect( page ).toMatchElement( blockClass );
	} );
} );
