/**
 * Webinar Field Display Block
 *
 * Displays a custom field value with optional prefix.
 *
 * @package ma_plugin
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl, RadioControl, SelectControl } from '@wordpress/components';
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
	const { fieldKey, prefix, prefixBold, fallbackText, iconType, iconName } = attributes;
	const { postId, postType } = context;

	// Icon types and names - this should match your icon library structure
	const iconTypes = ['outline', 'solid'];
	const iconNames = [
		{ label: __('None', 'ma-plugin'), value: '' },
		{ label: __('Check In', 'ma-plugin'), value: 'checkInAccommodationIcon' },
		{ label: __('Check Out', 'ma-plugin'), value: 'checkOutAccommodationIcon' },
		{ label: __('Clock', 'ma-plugin'), value: 'clockIcon' },
		{ label: __('Calendar', 'ma-plugin'), value: 'calendarIcon' },
		{ label: __('Person', 'ma-plugin'), value: 'personIcon' },
	];

	const blockProps = useBlockProps({
		className: 'wp-block-ma-plugin-webinar-field-display',
	});

	// Get the field value from post meta.
	const [meta] = useEntityProp('postType', postType, 'meta', postId);
	const fieldValue = meta?.[fieldKey] || fallbackText || __('(No value set)', 'ma-plugin');

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
				<PanelBody title={__('Icon Settings', 'ma-plugin')} initialOpen={false}>
					<SelectControl
						label={__('Icon', 'ma-plugin')}
						value={iconName}
						onChange={(value) => setAttributes({ iconName: value })}
						options={iconNames}
						help={__('Select an icon to display before the field value.', 'ma-plugin')}
					/>
					{iconName && (
						<RadioControl
							label={__('Icon Type', 'ma-plugin')}
							selected={iconType}
							onChange={(value) => setAttributes({ iconType: value })}
							options={iconTypes.map((type) => ({
								label: type.charAt(0).toUpperCase() + type.slice(1),
								value: type,
							}))}
						/>
					)}
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
	save: () => null, // Dynamic block - uses PHP render callback
});
