/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { TextControl } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import type { DataFormControlProps } from '@wordpress/dataviews/wp';

/**
 * Internal dependencies
 */
import { FormValues } from '../../types';
import { NAME_MAX_LENGTH, sanitizeName } from '../../utils/name';

/**
 * Edits the relationship name, correcting disallowed characters as they are typed.
 */
export function NameControl({ data, field, onChange }: DataFormControlProps<FormValues>) {
	// DataViews bundles its own components, whose generated IDs repeat ours.
	const id = useInstanceId(NameControl, 'content-connect-name');

	return (
		<TextControl
			id={id}
			label={field.label}
			help={field.description}
			value={data.rel_name}
			maxLength={NAME_MAX_LENGTH}
			onChange={(value) => onChange({ rel_name: sanitizeName(value) })}
			required
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
}
