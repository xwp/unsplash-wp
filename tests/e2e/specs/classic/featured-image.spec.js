/**
 * WordPress dependencies
 */
import { visitAdminPage } from '@wordpress/e2e-test-utils';
import { activatePlugin, deactivatePlugin } from '../../utils';

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
		const MEDIA_LIBRARY_BUTTON = '#set-post-thumbnail';
		const UNSPLASH_LIBRARY_BUTTON = '#menu-item-unsplash';

		// Click the media library button and wait for tab.
		await page.waitForSelector( MEDIA_LIBRARY_BUTTON );
		await page.click( MEDIA_LIBRARY_BUTTON );
		await page.waitForSelector( '.media-modal' );
		await page.waitForSelector( UNSPLASH_LIBRARY_BUTTON );
		await expect( page ).toMatchElement( UNSPLASH_LIBRARY_BUTTON );
	} );
} );
