/**
 * WordPress dependencies
 */
import { visitAdminPage } from '@wordpress/e2e-test-utils';
/**
 * Internal dependencies
 */
import { UNSPLASH_MODAL } from '../../constants';

const CONTAINER = '.attachments';

describe( 'Media Library', () => {
	beforeEach( async () => {
		await visitAdminPage( 'upload.php', 'mode=grid' );
		await page.waitForSelector( CONTAINER, {
			visible: true,
		} );
	} );

	it( 'attachment details has unsplash link', async () => {
		const btnSelector =
			CONTAINER + ' .attachment-preview.type-image:first-of-type';
		await page.waitForSelector( btnSelector );
		await page.evaluate( selector => {
			document.querySelector( selector ).click();
		}, btnSelector );

		await page.waitForSelector( UNSPLASH_MODAL, {
			visible: true,
		} );

		const blockClass = '.unsplash-author-link';
		await page.waitForSelector( blockClass );
		await expect( page ).toMatchElement( blockClass );
	} );
} );
