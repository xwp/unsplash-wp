/**
 * WordPress dependencies
 */
import { visitAdminPage } from '@wordpress/e2e-test-utils';
import { activatePlugin, clickButton, deactivatePlugin } from '../../utils';

describe( 'Classic editor', () => {
	beforeAll( async () => {
		await activatePlugin( 'classic-editor' );
	} );

	afterAll( async () => {
		await deactivatePlugin( 'classic-editor' );
	} );

	beforeEach( async () => {
		await visitAdminPage( 'post-new.php', {} );
		await clickButton( 'Add Media' );
		await page.waitForSelector( '.media-modal' );
		await page.waitForSelector( '#menu-item-unsplash' );
	} );

	it( 'should the tab exist', async () => {
		// Wait unsplash tab.
		await expect( page ).toMatchElement( '#menu-item-unsplash' );
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
		const btnSelect = '.media-button-insert';
		await page.evaluate( selector => {
			document.querySelector( selector ).click();
		}, btnSelect );
		await page.waitFor( 2000 );
		const imgClass = 'size-large';
		// Switch to HTML mode
		await expect( page ).toClick( '#content-html' );

		const textEditorContent = await page.$eval(
			'.wp-editor-area',
			element => element.value
		);
		expect( textEditorContent ).toContain( imgClass );
	} );
} );
