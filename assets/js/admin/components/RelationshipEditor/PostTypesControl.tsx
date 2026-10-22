/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { BaseControl, CheckboxControl, Flex } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import type { DataFormControlProps } from '@wordpress/dataviews/wp';

/**
 * Internal dependencies
 */
import { FormValues } from '../../types';

/**
 * Picks post types from a list of checkboxes, one per post type.
 *
 * The selection keeps the order of the post type list.
 */
export function PostTypesControl({
	data,
	field,
	onChange,
	hideLabelFromVision,
}: DataFormControlProps<FormValues>) {
	// DataViews bundles its own components, whose generated IDs repeat ours.
	const idPrefix = useInstanceId(PostTypesControl, 'content-connect-post-types');
	const elements = (field.elements ?? []) as { value: string; label: string }[];
	const selected = data.rel_to;

	const onToggle = (slug: string, isChecked: boolean) => {
		const next = elements
			.map((element) => element.value)
			.filter((value) => (value === slug ? isChecked : selected.includes(value)));

		onChange({ rel_to: next });
	};

	return (
		<fieldset className="content-connect-admin__checkbox-group">
			{!hideLabelFromVision && (
				<BaseControl.VisualLabel as="legend">{field.label}</BaseControl.VisualLabel>
			)}
			<Flex direction="column" gap={2}>
				{elements.map((element) => (
					<CheckboxControl
						key={element.value}
						id={`${idPrefix}-${element.value}`}
						label={element.label}
						checked={selected.includes(element.value)}
						onChange={(isChecked) => onToggle(element.value, isChecked)}
						__nextHasNoMarginBottom
					/>
				))}
			</Flex>
		</fieldset>
	);
}
