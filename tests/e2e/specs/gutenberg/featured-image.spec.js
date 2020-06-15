/**
 * WordPress dependencies
 */
import { createNewPost } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { clickButton, deactivatePlugin } from '../../utils';
import {
	UNSPLASH_CONTRAINER,
	UNSPLASH_LIBRARY_BUTTON,
	UNSPLASH_MODAL,
} from '../../constants';
/**
 * Tests the notices for the featured image.
 */
describe( 'Featured Image', () => {
	beforeAll( async () => {
		await deactivatePlugin( 'classic-editor' );
	} );

	beforeEach( async () => {
		await createNewPost();
		await clickButton( 'Document' );
		await clickButton( 'Featured image' );
		await clickButton( 'Set featured image' );
		await page.waitForSelector( UNSPLASH_MODAL, {
			visible: true,
		} );
	} );

	it( 'select image', async () => {
		await page.waitForSelector( UNSPLASH_MODAL );
		await page.waitForSelector( UNSPLASH_LIBRARY_BUTTON );
		await page.evaluate( selector => {
			document.querySelector( selector ).click();
		}, UNSPLASH_LIBRARY_BUTTON );
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
		const blockClass = '.editor-post-featured-image__preview';
		await page.waitForSelector( blockClass );
		await expect( page ).toMatchElement( blockClass );
	} );
} );
