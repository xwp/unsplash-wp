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
	} );

	it( 'should the tab exist', async () => {
		await clickButton( 'Add Media' );
		await page.waitForSelector( '.media-modal' );
		await page.waitForSelector( '#menu-item-unsplash' );
		// Wait unsplash tab.
		await expect( page ).toMatchElement( '#menu-item-unsplash' );
	} );
} );
