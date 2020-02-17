/**
 * WordPress dependencies
 */
import { createNewPost, insertBlock } from '@wordpress/e2e-test-utils';
import { clickButton } from '../../utils';

describe( 'Image Block', () => {

	beforeEach( async () => {
		await createNewPost( {} );
	} );

	it( 'should the tab exist', async () => {
		await insertBlock( 'Image' );
		await clickButton('Library');
		// Wait unsplash tab.
		await expect( page ).toMatchElement( '#menu-item-unsplash' );
	} );
} );
