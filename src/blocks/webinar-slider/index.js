/**
 * Medical Academic Enhancements Slider Block
 * 
 * @package ma-plugin
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';

// Import styles
import './editor.scss';
import './style.scss';

// Register the block
registerBlockType(metadata.name, {
	...metadata,
	edit: Edit,
});
