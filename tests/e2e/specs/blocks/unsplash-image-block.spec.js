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
			await page.$( '[data-type="unsplash/image"] .is-primary' )
		).not.toBeNull();
	} );

	it( 'should open unsplash modal', async () => {
		// Insert unsplash block.
		await insertBlock( 'Unsplash' );

		await clickButton( 'Search' );
		await page.waitForSelector( '.media-modal' );

		// Unsplash modal is open.
		expect( await page.$$( '.media-modal' ) ).toHaveLength( 1 );

		// Search field exists.
		expect( await page.$$( '#unsplash-search-input' ) ).toHaveLength( 1 );

		await page.waitForSelector( '.unsplash-attachment' );

		// 30 images are loaded.
		expect( await page.$$( '.unsplash-attachment' ) ).toHaveLength( 30 );
	} );
} );
