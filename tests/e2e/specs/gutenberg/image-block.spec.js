/**
 * WordPress dependencies
 */
import { createNewPost, insertBlock } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { clickButton, clickSelector, deactivatePlugin } from '../../utils';
import {
	UNSPLASH_LIBRARY_BUTTON,
	UNSPLASH_CONTRAINER,
	UNSPLASH_LIBRARY_SEARCH_INPUT,
	UNSPLASH_MODAL,
	UNSPLASH_NO_RESULTS,
} from '../../constants';

describe( 'Image Block', () => {
	beforeAll( async () => {
		await deactivatePlugin( 'classic-editor' );
	} );

	beforeEach( async () => {
		await createNewPost( {} );

		// Insert image block.
		await insertBlock( 'Image' );

		// Click the media library button and wait for tab.
		await clickButton( 'Media Library' );
		await page.waitForSelector( UNSPLASH_MODAL, {
			visible: true,
		} );
		await clickSelector( UNSPLASH_LIBRARY_BUTTON );
	} );

	it( 'should the tab exist', async () => {
		await expect( page ).toMatchElement( UNSPLASH_LIBRARY_BUTTON );
	} );

	it( 'Search: results found', async () => {
		await page.waitForSelector( UNSPLASH_LIBRARY_SEARCH_INPUT );
		await page.focus( UNSPLASH_LIBRARY_SEARCH_INPUT );
		await page.keyboard.type( 'WordPress' );
		await page.waitForSelector( UNSPLASH_CONTRAINER, {
			visible: true,
		} );
		await expect( page ).toMatchElement( UNSPLASH_CONTRAINER );
	} );

	it( 'Search: no results found', async () => {
		await page.waitForSelector( UNSPLASH_LIBRARY_SEARCH_INPUT );
		await page.focus( UNSPLASH_LIBRARY_SEARCH_INPUT );
		await page.keyboard.type( 'dsfdsfs' );
		await page.waitForSelector( UNSPLASH_NO_RESULTS, {
			visible: true,
		} );
		await expect( page ).toMatchElement( UNSPLASH_NO_RESULTS );
	} );

	it( 'insert image', async () => {
		await page.waitForSelector( UNSPLASH_CONTRAINER );
		const btnSelector =
			UNSPLASH_CONTRAINER + ' .unsplash-attachment:first-of-type';
		await clickSelector( btnSelector );
		const btnSelect = '.media-button-select';
		await clickSelector( btnSelect );
		await page.waitForSelector( UNSPLASH_MODAL, {
			visible: false,
		} );
		const blockClass = '.wp-block-image';
		await page.waitForSelector( blockClass );
		await expect( page ).toMatchElement( blockClass );
	} );
} );
