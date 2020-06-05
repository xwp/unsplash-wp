/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';

const transforms = {
	to: [
		{
			type: 'block',
			blocks: [ 'core/image' ],
			transform: attributes => {
				return createBlock( 'core/image', attributes );
			},
		},
	],
};

export default transforms;
