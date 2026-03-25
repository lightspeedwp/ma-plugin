/**
 * @file QueryControls.js
 * @description Component for controlling query parameters in the UI.
 * @todo Add prop types and improve test coverage.
 */
/**
 * Query Controls Component
 *
 * Reusable query configuration controls for collection blocks.
 *
 * @package ma_plugin
 */

import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import TaxonomyFilter from '../TaxonomyFilter';

/**
 * QueryControls component.
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.query    Current query settings.
 * @param {Function} props.onChange Callback when query changes.
 *
 * @return {Element} QueryControls component.
 */
export default function QueryControls({ query, onChange }) {
	const {
		perPage = 6,
		order = 'desc',
		orderBy = 'date',
		featured = false,
		taxQuery = null,
	} = query;

	const updateQuery = (updates) => {
		onChange({ ...query, ...updates });
	};

	return (
		<InspectorControls>
			<PanelBody title={__('Query Settings', 'ma-plugin')}>
				<RangeControl
					   label={__('Number of Items', 'ma-plugin')}
					value={perPage}
					onChange={(value) => updateQuery({ perPage: value })}
					min={1}
					max={24}
				/>
				<SelectControl
					   label={__('Order By', 'ma-plugin')}
					value={orderBy}
					options={[
						   { label: __('Date', 'ma-plugin'), value: 'date' },
						   {
							   label: __('Title', 'ma-plugin'),
							   value: 'title',
						   },
						   {
							   label: __('Modified', 'ma-plugin'),
							   value: 'modified',
						   },
						   {
							   label: __('Random', 'ma-plugin'),
							   value: 'rand',
						   },
						   {
							   label: __('Menu Order', 'ma-plugin'),
							   value: 'menu_order',
						   },
					]}
					onChange={(value) => updateQuery({ orderBy: value })}
				/>
				<SelectControl
					   label={__('Order', 'ma-plugin')}
					value={order}
					options={[
						   {
							   label: __('Descending', 'ma-plugin'),
							   value: 'desc',
						   },
						   {
							   label: __('Ascending', 'ma-plugin'),
							   value: 'asc',
						   },
					]}
					onChange={(value) => updateQuery({ order: value })}
				/>
				<ToggleControl
					   label={__('Featured Only', 'ma-plugin')}
					checked={featured}
					onChange={(value) => updateQuery({ featured: value })}
				/>
			</PanelBody>

			   <PanelBody
				   title={__('Filter by Taxonomy', 'ma-plugin')}
				   initialOpen={false}
			   >
				<TaxonomyFilter
					   taxonomy="_category"
					   value={taxQuery?.['_category'] || []}
					onChange={(termIds) =>
						updateQuery({
							taxQuery: termIds.length
								? { 'example-plugin_category': termIds }
								: null,
						})
					}
					   label={__('Categories', 'ma-plugin')}
				/>
			</PanelBody>
		</InspectorControls>
	);
}
