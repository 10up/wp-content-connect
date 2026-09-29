/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import type { Field, Form } from '@wordpress/dataviews/wp';

/**
 * Internal dependencies
 */
import { FormValues, PostTypeElement, RelType } from '../../types';
import { NameControl } from './NameControl';
import { PostTypesControl } from './PostTypesControl';

const isPostToPost = (item: FormValues) => item.rel_type === 'post-to-post';

const validateMaxItems = (value: number) =>
	Number.isInteger(value) && value >= 1
		? null
		: __('Enter a whole number of at least 1.', 'wp-content-connect');

/**
 * Returns the relationship form fields.
 */
export const getFields = (postTypes: PostTypeElement[]): Field<FormValues>[] => [
	{
		id: 'title',
		type: 'text',
		label: __('Label', 'wp-content-connect'),
		description: __('Identifies the relationship in this list.', 'wp-content-connect'),
		isValid: { required: true },
	},
	{
		id: 'rel_type',
		type: 'text',
		label: __('Type', 'wp-content-connect'),
		Edit: 'radio',
		elements: [
			{ value: 'post-to-post', label: __('Posts to posts', 'wp-content-connect') },
			{ value: 'post-to-user', label: __('Posts to users', 'wp-content-connect') },
		],
	},
	{
		id: 'rel_name',
		type: 'text',
		label: __('Name', 'wp-content-connect'),
		description: __(
			'Lowercase letters, numbers, dashes and underscores. Cannot change after saving.',
			'wp-content-connect',
		),
		Edit: NameControl,
		// NameControl corrects the value as it is typed, so only emptiness needs checking.
		isValid: { required: true },
	},
	{
		id: 'rel_from',
		type: 'text',
		label: __('Post type', 'wp-content-connect'),
		Edit: 'select',
		// The empty option keeps the select from showing a post type that is not chosen.
		elements: [
			{ value: '', label: __('Select a post type', 'wp-content-connect') },
			...postTypes,
		],
		isValid: { required: true },
	},
	{
		id: 'from_enable_ui',
		type: 'boolean',
		label: __('Show panel in the editor', 'wp-content-connect'),
		Edit: 'toggle',
	},
	{
		id: 'from_label',
		type: 'text',
		label: __('Panel title', 'wp-content-connect'),
		placeholder: __('Defaults to the name', 'wp-content-connect'),
		isVisible: (item) => item.from_enable_ui,
	},
	{
		id: 'from_sortable',
		type: 'boolean',
		label: __('Allow reordering', 'wp-content-connect'),
		Edit: 'toggle',
		isVisible: (item) => item.from_enable_ui,
	},
	{
		id: 'from_max_items',
		type: 'integer',
		label: __('Maximum items', 'wp-content-connect'),
		isVisible: (item) => item.from_enable_ui,
		isValid: { custom: (item) => validateMaxItems(item.from_max_items) },
	},
	{
		id: 'rel_to',
		type: 'array',
		label: __('Related post types', 'wp-content-connect'),
		elements: postTypes,
		Edit: PostTypesControl,
		isVisible: isPostToPost,
		isValid: {
			custom: (item) =>
				isPostToPost(item) && item.rel_to.length === 0
					? __('Choose at least one related post type.', 'wp-content-connect')
					: null,
		},
	},
	{
		id: 'to_enable_ui',
		type: 'boolean',
		label: __('Also show a panel on related posts (bidirectional)', 'wp-content-connect'),
		Edit: 'toggle',
		isVisible: isPostToPost,
	},
	{
		id: 'to_label',
		type: 'text',
		label: __('Panel title', 'wp-content-connect'),
		placeholder: __('Defaults to the name', 'wp-content-connect'),
		isVisible: (item) => isPostToPost(item) && item.to_enable_ui,
	},
	{
		id: 'to_sortable',
		type: 'boolean',
		label: __('Allow reordering', 'wp-content-connect'),
		Edit: 'toggle',
		isVisible: (item) => isPostToPost(item) && item.to_enable_ui,
	},
	{
		id: 'to_max_items',
		type: 'integer',
		label: __('Maximum items', 'wp-content-connect'),
		isVisible: (item) => isPostToPost(item) && item.to_enable_ui,
		isValid: {
			custom: (item) =>
				isPostToPost(item) && item.to_enable_ui
					? validateMaxItems(item.to_max_items)
					: null,
		},
	},
];

/**
 * Fields that identify stored connections. Once a relationship is saved they
 * leave the form and are summarized in a note instead.
 */
export const LOCKED_FIELDS = ['rel_type', 'rel_name', 'rel_from', 'rel_to'];

export type FormSection = {
	id: string;
	title: string;
	fields: string[];
};

/**
 * Returns the editor sections: general settings, then one section per side.
 */
export const getSections = (relType: RelType, isEditing: boolean): FormSection[] => {
	const hasRelatedPostTypes = relType === 'post-to-post';
	const editable = (ids: string[]) =>
		isEditing ? ids.filter((id) => !LOCKED_FIELDS.includes(id)) : ids;

	const sections: FormSection[] = [
		{
			id: 'general',
			title: __('General', 'wp-content-connect'),
			fields: editable(['title', 'rel_type', 'rel_name']),
		},
		{
			id: 'from',
			title: __('Post type', 'wp-content-connect'),
			fields: editable([
				'rel_from',
				'from_enable_ui',
				'from_label',
				'from_sortable',
				'from_max_items',
			]),
		},
	];

	if (hasRelatedPostTypes) {
		sections.push({
			id: 'to',
			title: __('Related to', 'wp-content-connect'),
			fields: editable(['rel_to', 'to_enable_ui', 'to_label', 'to_sortable', 'to_max_items']),
		});
	}

	return sections;
};

/**
 * Returns the form for a list of fields, labels above their controls.
 */
export const getForm = (fields: string[]): Form => ({
	layout: { type: 'regular', labelPosition: 'top' },
	fields,
});
