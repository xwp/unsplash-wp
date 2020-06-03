/**
 * WordPress dependencies
 */
import { createNewPost, insertBlock } from '@wordpress/e2e-test-utils';
import { clickButton } from '../../utils';

describe( 'Unsplash Image Block', () => {
	beforeEach( async () => {
		await createNewPost( {} );
	} );

	it( 'should be inserted and render placeholder', async () => {
		// Insert unsplash block.
		await insertBlock( 'Unsplash' );

		// Check if block was inserted
		expect( await page.$( '[data-type="unsplash/image"]' ) ).not.toBeNull();

		// Show placeholder instructions.
		expect(
			await page.$(
				'[data-type="unsplash/image"] .components-placeholder__instructions'
			)
		).not.toBeNull();

		// Show search button.
		expect(
			await page.$( '[data-type="unsplash/image"] .is-secondary' )
		).not.toBeNull();
	} );

	it( 'should open unsplash modal', async () => {
		// Insert unsplash block.
		await insertBlock( 'Unsplash' );

		await clickButton( 'Search Unsplash' );
		await page.waitForSelector( '.media-modal' );

		// Unsplash modal is open.
		expect( await page.$( '.media-modal' ) ).not.toBeNull();

		// Search field exists.
		expect( await page.$( '#unsplash-search-input' ) ).not.toBeNull();

		await page.waitForSelector( '.unsplash-attachment' );

		// Atleast one image is loaded.
		expect(
			( await page.$$( '.unsplash-attachment' ) ).length
		).toBeGreaterThan( 0 );
	} );

	it( 'should select and insert an image', async () => {
		// Insert unsplash block.
		await insertBlock( 'Unsplash' );

		await clickButton( 'Search Unsplash' );
		await page.waitForSelector( '.media-modal' );
		await page.waitForSelector( '#unsplash-search-input', { visible: true } );

		const input = await page.$( '#unsplash-search-input' );

		await input.click();

		// These search terms should return the image with ID `ZkjvMnVz-7w`
		await page.keyboard.type( 'jar bottle shaker outdoors lowkey' );

		await page.waitForSelector( '.unsplash-attachment[data-id="ZkjvMnVz-7w"]' );

		const attachment = await page.$(
			'.unsplash-attachment[data-id="ZkjvMnVz-7w"]'
		);
		attachment.click();

		const button = await page.$( '.media-button-select' );
		button.click();

		await page.waitForSelector( '.wp-block-unsplash-image' );

		// Image is inserted.
		expect( await page.$( '.wp-block-unsplash-image' ) ).not.toBeNull();
	} );
} );
