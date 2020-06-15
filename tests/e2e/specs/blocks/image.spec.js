/**
 * WordPress dependencies
 */
import { createNewPost, insertBlock } from '@wordpress/e2e-test-utils';
import { clickButton } from '../../utils';

/**
 * Internal dependencies
 */
import { UNSPLASH_LIBRARY_SEARCH_INPUT, UNSPLASH_MODAL } from '../../constants';

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
		await page.waitForSelector( UNSPLASH_MODAL, {
			visible: true,
		} );

		// Unsplash modal is open.
		expect( await page.$( UNSPLASH_MODAL ) ).not.toBeNull();

		// Search field exists.
		expect( await page.$( '#unsplash-search-input' ) ).not.toBeNull();

		await page.waitForSelector( '.unsplash-attachment' );

		// Atleast one image is loaded.
		expect(
			( await page.$$( '.unsplash-attachment' ) ).length
		).toBeGreaterThan( 0 );
	} );

	it( 'should select and insert an image', async () => {
		await page.setDefaultTimeout( 30000 );

		// Insert unsplash block.
		await insertBlock( 'Unsplash' );

		await clickButton( 'Search Unsplash' );

		await page.waitForSelector( UNSPLASH_MODAL, {
			visible: true,
		} );

		await page.waitForSelector( UNSPLASH_LIBRARY_SEARCH_INPUT );

		await page.focus( UNSPLASH_LIBRARY_SEARCH_INPUT );

		const imageSelector =
			'.unsplash-browser .attachments .unsplash-attachment:first-of-type';

		await page.waitForSelector( imageSelector );

		await page.evaluate( selector => {
			document.querySelector( selector ).click();
		}, imageSelector );

		const btnSelect = '.media-button-select';
		await page.evaluate( selector => {
			document.querySelector( selector ).click();
		}, btnSelect );

		await page.waitForSelector( '.wp-block-unsplash-image' );

		// Image is inserted.
		const blockClass = '.wp-block-unsplash-image';
		await page.waitForSelector( blockClass );
		await expect( page ).toMatchElement( blockClass );
	} );
} );
