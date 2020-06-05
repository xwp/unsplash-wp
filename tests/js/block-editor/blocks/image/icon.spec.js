/**
 * External dependencies
 */
import '@testing-library/jest-dom/extend-expect';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Icon from '../../../../../assets/src/block-editor/blocks/image/icon';

describe( 'blocks: unspash/image: icon', () => {
	it( 'matches snapshot', () => {
		const wrapper = render( <Icon /> );
		expect( wrapper ).toMatchSnapshot();
	} );
} );
