/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	name as unsplashImageName,
	settings as unsplashImageSettings,
} from './blocks/unsplash';

registerBlockType( unsplashImageName, unsplashImageSettings );
