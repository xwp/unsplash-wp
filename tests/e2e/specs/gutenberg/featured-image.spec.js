/**
 * WordPress dependencies
 */
import { createNewPost } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { clickButton, deactivatePlugin, clickSelector } from '../../utils';
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
		await page.waitForSelector( UNSPLASH_LIBRARY_BUTTON );
		await clickSelector( UNSPLASH_LIBRARY_BUTTON );
		await page.waitForSelector( UNSPLASH_CONTRAINER, {
			visible: true,
		} );
		const btnSelector =
			UNSPLASH_CONTRAINER + ' .unsplash-attachment:first-of-type';
		await clickSelector( btnSelector );
		const btnSelect = '.media-button-select';
		await page.waitForSelector( btnSelect );
		await expect( page ).toClick( btnSelect );
	} );
} );
