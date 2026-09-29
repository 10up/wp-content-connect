/**
 * Internal dependencies
 */
import {
	CodeRelationship,
	RelationshipMeta,
	CustomRelationshipRecord,
	FormValues,
	Row,
	SideArgs,
} from '../types';

export const DEFAULT_MAX_ITEMS = 100;

const defaultSideArgs = (isFrom: boolean): SideArgs => ({
	enable_ui: isFrom,
	sortable: false,
	max_items: DEFAULT_MAX_ITEMS,
});

export const getRecordTitle = (record: CustomRelationshipRecord): string => {
	if (typeof record.title === 'string') {
		return record.title;
	}

	return record.title?.raw ?? record.title?.rendered ?? '';
};

export const recordToRow = (record: CustomRelationshipRecord): Row => {
	const { meta } = record;
	const fromArgs = { ...defaultSideArgs(true), ...meta.from_args };
	const toArgs = { ...defaultSideArgs(false), ...meta.to_args };
	const isPostToPost = meta.rel_type === 'post-to-post';

	let status: Row['status'] = 'disabled';
	if (record.status === 'publish') {
		status = record.registration_error ? 'error' : 'active';
	}

	return {
		id: `custom-${record.id}`,
		source: 'custom',
		postId: record.id,
		label: getRecordTitle(record) || meta.rel_name,
		relType: meta.rel_type,
		relKey: record.rel_key,
		relName: meta.rel_name,
		from: meta.rel_from,
		to: isPostToPost ? meta.rel_to : [],
		fromArgs,
		toArgs,
		status,
		error: record.registration_error,
		bidirectional: isPostToPost && toArgs.enable_ui,
	};
};

export const codeToRow = (relationship: CodeRelationship): Row => {
	const isPostToPost = relationship.rel_type === 'post-to-post';

	const fromArgs: SideArgs = isPostToPost
		? {
				enable_ui: relationship.from?.enable_ui ?? true,
				sortable: relationship.from?.sortable ?? false,
				max_items: relationship.from?.max_items ?? DEFAULT_MAX_ITEMS,
				labels: relationship.from?.labels,
			}
		: {
				enable_ui: relationship.enable_ui ?? true,
				sortable: relationship.sortable ?? false,
				max_items: relationship.max_items ?? DEFAULT_MAX_ITEMS,
				labels: relationship.labels,
			};

	const toArgs: SideArgs = isPostToPost
		? {
				enable_ui: relationship.to?.enable_ui ?? false,
				sortable: relationship.to?.sortable ?? false,
				max_items: relationship.to?.max_items ?? DEFAULT_MAX_ITEMS,
				labels: relationship.to?.labels,
			}
		: defaultSideArgs(false);

	return {
		id: `code-${relationship.rel_key}`,
		source: 'code',
		label: fromArgs.labels?.name || relationship.rel_name,
		relType: relationship.rel_type,
		relKey: relationship.rel_key,
		relName: relationship.rel_name,
		from: (isPostToPost ? relationship.from?.object_type : relationship.post_type) ?? '',
		to: isPostToPost ? (relationship.to?.object_types ?? []) : [],
		fromArgs,
		toArgs,
		status: 'active',
		bidirectional: isPostToPost && toArgs.enable_ui,
	};
};

export const emptyFormValues = (): FormValues => ({
	title: '',
	rel_type: 'post-to-post',
	rel_from: '',
	rel_to: [],
	rel_name: '',
	from_enable_ui: true,
	from_label: '',
	from_sortable: false,
	from_max_items: DEFAULT_MAX_ITEMS,
	to_enable_ui: false,
	to_label: '',
	to_sortable: false,
	to_max_items: DEFAULT_MAX_ITEMS,
});

export const rowToFormValues = (row: Row): FormValues => ({
	title: row.label,
	rel_type: row.relType,
	rel_from: row.from,
	rel_to: row.to,
	rel_name: row.relName,
	from_enable_ui: row.fromArgs.enable_ui,
	from_label: row.fromArgs.labels?.name ?? '',
	from_sortable: row.fromArgs.sortable,
	from_max_items: row.fromArgs.max_items,
	to_enable_ui: row.toArgs.enable_ui,
	to_label: row.toArgs.labels?.name ?? '',
	to_sortable: row.toArgs.sortable,
	to_max_items: row.toArgs.max_items,
});

const toSideArgs = (
	enableUi: boolean,
	label: string,
	sortable: boolean,
	maxItems: number,
): SideArgs => ({
	enable_ui: enableUi,
	sortable,
	max_items: Math.max(1, Number(maxItems) || DEFAULT_MAX_ITEMS),
	labels: { name: label.trim() },
});

export const formValuesToRecord = (
	values: FormValues,
): { title: string; meta: RelationshipMeta } => {
	const isPostToPost = values.rel_type === 'post-to-post';

	return {
		title: values.title.trim(),
		meta: {
			rel_type: values.rel_type,
			rel_from: values.rel_from,
			rel_to: isPostToPost ? values.rel_to : [],
			rel_name: values.rel_name,
			from_args: toSideArgs(
				values.from_enable_ui,
				values.from_label,
				values.from_sortable,
				values.from_max_items,
			),
			to_args: toSideArgs(
				isPostToPost && values.to_enable_ui,
				values.to_label,
				values.to_sortable,
				values.to_max_items,
			),
		},
	};
};
