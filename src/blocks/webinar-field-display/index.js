/**
 *  Field Display Block
 *
 * Displays a custom field value with optional prefix.
 *
 * @package ma_plugin
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl, SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';

import './editor.scss';
import './style.scss';
import metadata from './block.json';

/**
 * Edit component for the field display block.
 *
 * @param {Object} props Block props.
 * @return {JSX.Element} Block edit component.
 */
const Edit = (props) => {
	const { attributes, setAttributes, context } = props;
	const { fieldKey, prefix, prefixBold, fallbackText } = attributes;
	const { postId, postType } = context;

	const blockProps = useBlockProps({
		className: 'wp-block-ma-plugin-webinar-field-display',
	});

	// Get the field value from post meta.
	const [meta] = useEntityProp('postType', postType, 'meta', postId);
	const fieldValue = meta?.[fieldKey] || fallbackText || __('(No value set)', 'ma-plugin');

	// Get all available post meta fields for this post type.
	const { availableFields } = useSelect(
		(select) => {
			const { getEntityRecord } = select('core');
			const postTypeObject = getEntityRecord('root', 'postType', postType);
			
			// Get meta fields from post type
			const metaFields = [];
			if (postTypeObject?.meta_box?.['normal']?.['default']) {
				Object.keys(postTypeObject.meta_box.normal.default).forEach((key) => {
					metaFields.push({ label: key, value: key });
				});
			}

			return {
				availableFields: metaFields.length > 0 ? metaFields : [
					{ label: __('No fields available', 'ma-plugin'), value: '' }
				],
			};
		},
		[postType]
	);

	// Format display value with prefix.
	const displayValue = () => {
		let display = '';
		
		if (prefix) {
			const prefixText = prefix.trim();
			const needsSpace = !/[\s\p{P}]$/u.test(prefixText);
			const formattedPrefix = prefixText + (needsSpace ? ' ' : '');
			
			if (prefixBold) {
				display = <><strong>{formattedPrefix}</strong>{fieldValue}</>;
			} else {
				display = <>{formattedPrefix}{fieldValue}</>;
			}
		} else {
			display = fieldValue;
		}
		
		return display;
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Field Settings', 'ma-plugin')} initialOpen={true}>
					<TextControl
						label={__('Field Key', 'ma-plugin')}
						value={fieldKey}
						onChange={(value) => setAttributes({ fieldKey: value })}
						help={__('Enter the meta key of the custom field to display.', 'ma-plugin')}
					/>
					<TextControl
						label={__('Fallback Text', 'ma-plugin')}
						value={fallbackText}
						onChange={(value) => setAttributes({ fallbackText: value })}
						help={__('Text to display when field is empty.', 'ma-plugin')}
					/>
				</PanelBody>
				<PanelBody title={__('Prefix Settings', 'ma-plugin')} initialOpen={false}>
					<TextControl
						label={__('Prefix Text', 'ma-plugin')}
						value={prefix}
						onChange={(value) => setAttributes({ prefix: value })}
						help={__('Text to display before the field value (e.g., "Price:", "From:").', 'ma-plugin')}
					/>
					<ToggleControl
						label={__('Bold Prefix', 'ma-plugin')}
						checked={prefixBold}
						onChange={(value) => setAttributes({ prefixBold: value })}
						help={__('Make the prefix text bold.', 'ma-plugin')}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<p className="field-display-value">
					{displayValue()}
				</p>
			</div>
		</>
	);
};

registerBlockType(metadata.name, {
	...metadata,
	edit: Edit,
});
