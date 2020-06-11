/**
 * WordPress dependencies
 */
import { createNewPost, insertBlock } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { clickButton, clickSelector } from '../../utils';
import {
	UNSPLASH_LIBRARY_BUTTON,
	UNSPLASH_CONTRAINER,
	UNSPLASH_MODAL,
} from '../../constants';

describe( 'Attachment Details', () => {
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

	it( 'should show pre-import attachment details', async () => {
		await page.waitForSelector( UNSPLASH_CONTRAINER );
		const btnSelector =
			UNSPLASH_CONTRAINER + ' .unsplash-attachment:first-of-type';
		await clickSelector( btnSelector );
		const attachmentDetails = '.unsplash-attachment-details';
		await page.waitForSelector( attachmentDetails );

		expect( await page.$( `${ attachmentDetails } .details` ) ).not.toBeNull();

		console.log( // eslint-disable-line
			await page.evaluate(
				node => node.innerHTML,
				await page.$( `${ attachmentDetails } .details` )
			)
		);

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
