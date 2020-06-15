/**
 * WordPress dependencies
 */
import { visitAdminPage } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { activatePlugin, clickButton, deactivatePlugin } from '../../utils';
import {
	UNSPLASH_CONTRAINER,
	UNSPLASH_LIBRARY_BUTTON,
	UNSPLASH_MODAL,
} from '../../constants';

const MEDIA_LIBRARY_BUTTON = '#set-post-thumbnail';

describe( 'Classic editor', () => {
	beforeAll( async () => {
		await activatePlugin( 'classic-editor' );
	} );

	afterAll( async () => {
		await deactivatePlugin( 'classic-editor' );
	} );

	beforeEach( async () => {
		await visitAdminPage( 'post-new.php', {} );
		await page.waitForSelector( MEDIA_LIBRARY_BUTTON );
		await page.click( MEDIA_LIBRARY_BUTTON );
		await page.waitForSelector( UNSPLASH_MODAL, {
			visible: true,
		} );
		await page.waitForSelector( UNSPLASH_LIBRARY_BUTTON );
	} );

	it( 'should contain the Unsplash tab', async () => {
		// Click the media library button and wait for tab.
		await expect( page ).toMatchElement( UNSPLASH_LIBRARY_BUTTON );
	} );

	it( 'select image', async () => {
		await page.waitForSelector( UNSPLASH_CONTRAINER );
		const btnSelector =
			'.unsplash-browser .attachments .unsplash-attachment:first-of-type';
		await page.waitForSelector( btnSelector );
		await page.evaluate( selector => {
			document.querySelector( selector ).click();
		}, btnSelector );
		await clickButton( 'Set featured image' );
		const blockClass = '.size-post-thumbnail';
		await page.waitForSelector( blockClass );
		await expect( page ).toMatchElement( blockClass );
	} );
} );
