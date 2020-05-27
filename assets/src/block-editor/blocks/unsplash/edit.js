/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Placeholder } from '@wordpress/components';

/**
 * Internal dependencies
 */
import icon from './icon';

const ImageEdit = () => {
	return (
		<Placeholder
			icon={ icon }
			label={ __( 'Unsplash', 'unsplash' ) }
			instructions={ __(
				"Search and select from the internet's source of freely usable images",
				'unsplash'
			) }
			className={ 'placeholderClassName' }
		>
			<Button
				isPrimary
				onClick={ event => {
					event.stopPropagation();
					console.log( 'open' );
				} }
			>
				{ __( 'Search', 'unsplash' ) }
			</Button>
		</Placeholder>
	);
};

export default ImageEdit;
