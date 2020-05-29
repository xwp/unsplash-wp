/**
 * WordPress dependencies
 */
import { visitAdminPage } from '@wordpress/e2e-test-utils';

const inputSelector = '#unsplash_access_key';
const btnSelect = '#submit.button-primary';

describe( 'Settings', () => {
	beforeEach( async () => {
		await visitAdminPage( 'options-general.php', 'page=unsplash' );
		await page.evaluate( selector => {
			document.querySelector( selector ).value = '';
		}, inputSelector );
		await page.evaluate( selector => {
			document.querySelector( selector ).click();
		}, btnSelect );
		await page.waitForNavigation();
	} );

	afterEach( async () => {
		await visitAdminPage( 'options-general.php', 'page=unsplash' );
		await page.evaluate( selector => {
			document.querySelector( selector ).value = '';
		}, inputSelector );
		await page.evaluate( selector => {
			document.querySelector( selector ).click();
		}, btnSelect );
		await page.waitForNavigation()
	} );

	it( 'page input exist', async () => {
		// Wait input tab.
		await expect( page ).toMatchElement( inputSelector );
	} );

	it( 'Valid key the input exist', async () => {
		await page.focus( inputSelector );
		await page.keyboard.type( 'valid-key' );
		await page.evaluate( selector => {
			document.querySelector( selector ).click();
		}, btnSelect );
		await page.waitForNavigation()

		const NO_RESULTS = '.notice-error.notice-unsplash';
		await page.waitForSelector( NO_RESULTS );
		await expect( page ).toMatchElement( NO_RESULTS );
	} );
} );
