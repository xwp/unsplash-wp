/**
 * WordPress dependencies
 */
import { createNewPost, insertBlock } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { clickButton, clickSelector, deactivatePlugin } from '../../utils';
import {
	UNSPLASH_LIBRARY_BUTTON,
	UNSPLASH_CONTRAINER,
	UNSPLASH_LIBRARY_SEARCH_INPUT,
	UNSPLASH_MODAL,
	UNSPLASH_NO_RESULTS,
} from '../../constants';

describe( 'Image Block', () => {
	beforeAll( async () => {
		await deactivatePlugin( 'classic-editor' );
	} );

	beforeEach( async () => {
		await createNewPost( {} );

		// Insert image block.
		await insertBlock( 'Image' );

		// Click the media library button and wait for tab.
		await clickButton( 'Media Library' );
		await page.waitForSelector( UNSPLASH_MODAL, {
			visible: true,
		} );
		await clickSelector( UNSPLASH_LIBRARY_BUTTON );
	} );

	it( 'should contain the Unsplash tab', async () => {
		await expect( page ).toMatchElement( UNSPLASH_LIBRARY_BUTTON );
	} );

	it( 'Search: results found', async () => {
		await page.waitForSelector( UNSPLASH_LIBRARY_SEARCH_INPUT );
		await page.focus( UNSPLASH_LIBRARY_SEARCH_INPUT );
		await page.keyboard.type( 'WordPress' );
		await page.waitForSelector( UNSPLASH_CONTRAINER );
		await expect( page ).toMatchElement( UNSPLASH_CONTRAINER );
	} );

	it( 'Search: no results found', async () => {
		await page.waitForSelector( UNSPLASH_LIBRARY_SEARCH_INPUT );
		await page.focus( UNSPLASH_LIBRARY_SEARCH_INPUT );
		await page.keyboard.type( 'dsfdsfs' );
		await page.waitForSelector( UNSPLASH_NO_RESULTS );
		await expect( page ).toMatchElement( UNSPLASH_NO_RESULTS );
	} );

	it( 'insert image', async () => {
		await page.waitForSelector( UNSPLASH_CONTRAINER );
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

	it( 'should show post-import attachment details', async () => {
		await clickSelector( '#menu-item-browse' );
		await page.waitForSelector( '.attachments-browser .attachment' );
		await clickSelector( '.attachments-browser .attachment' );
		const attachmentDetails = '.unsplash-attachment-details';
		await page.waitForSelector( attachmentDetails );

		expect( await page.$( `${ attachmentDetails } .details` ) ).not.toBeNull();

		await page.waitForSelector( `${ attachmentDetails } .details .author` );

		expect(
			await page.evaluate(
				author => author.innerText,
				await page.$( `${ attachmentDetails } .details .author` )
			)
		).toContain( 'Photo by:' );

		expect(
			await page.evaluate(
				filename => filename.innerHTML,
				await page.$( `${ attachmentDetails } .details .filename` )
			)
		).toContain( '<strong>File name:</strong>' );

		expect(
			await page.evaluate(
				uploaded => uploaded.innerText,
				await page.$( `${ attachmentDetails } .details .uploaded` )
			)
		).toContain( 'Date:' );

		const originalImage = await page.evaluate(
			details => details.innerHTML,
			await page.$( `${ attachmentDetails } .details` )
		);

		expect( originalImage ).toContain( '<strong>Original image:</strong>' );
		expect( originalImage ).toMatch(
			new RegExp( /https:\/\/unsplash.com\/photos\/[^"]+/ )
		);
	} );
} );
