/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { Tooltip } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import { DataViews, filterSortAndPaginate } from '@wordpress/dataviews/wp';
import type { Action, Field, View } from '@wordpress/dataviews/wp';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { code, copy, pencil, trash } from '@wordpress/icons';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { PostTypeElement, Row } from '../types';
import { RELATIONSHIP_POST_TYPE } from '../hooks/use-post-type-elements';
import { CopyAsPhpModal, DeleteModal } from './ActionModals';

type RelationshipsListProps = {
	rows: Row[];
	isLoading: boolean;
	postTypes: PostTypeElement[];
	onEdit(row: Row): void;
	onDuplicate(row: Row): void;
};

const DEFAULT_VIEW: View = {
	type: 'table',
	search: '',
	filters: [],
	page: 1,
	perPage: 20,
	sort: { field: 'label', direction: 'asc' },
	titleField: 'label',
	fields: ['relType', 'from', 'to', 'bidirectional', 'source', 'status'],
	layout: {},
};

const DEFAULT_LAYOUTS = { table: {} };

const isCustom = (row: Row) => row.source === 'custom';

export function RelationshipsList({
	rows,
	isLoading,
	postTypes,
	onEdit,
	onDuplicate,
}: RelationshipsListProps) {
	const [view, setView] = useState<View>(DEFAULT_VIEW);
	const { saveEntityRecord } = useDispatch(coreStore);
	const { createSuccessNotice, createErrorNotice } = useDispatch(noticesStore);

	const fields = useMemo<Field<Row>[]>(
		() => [
			{
				id: 'label',
				type: 'text',
				label: __('Label', 'wp-content-connect'),
				enableHiding: false,
				enableGlobalSearch: true,
			},
			{
				id: 'relName',
				type: 'text',
				label: __('Name', 'wp-content-connect'),
				enableGlobalSearch: true,
			},
			{
				id: 'relType',
				type: 'text',
				label: __('Type', 'wp-content-connect'),
				elements: [
					{ value: 'post-to-post', label: __('Posts to posts', 'wp-content-connect') },
					{ value: 'post-to-user', label: __('Posts to users', 'wp-content-connect') },
				],
				filterBy: { operators: ['is'] },
			},
			{
				id: 'from',
				type: 'text',
				label: __('Post type', 'wp-content-connect'),
				elements: postTypes,
				filterBy: { operators: ['isAny'] },
			},
			{
				id: 'to',
				type: 'array',
				label: __('Related to', 'wp-content-connect'),
				elements: postTypes,
				render: ({ item }) => {
					if (item.relType === 'post-to-user') {
						return <>{__('Users', 'wp-content-connect')}</>;
					}

					const labels = item.to.map(
						(slug) =>
							postTypes.find((postType) => postType.value === slug)?.label ?? slug,
					);

					return <>{labels.join(', ')}</>;
				},
				enableSorting: false,
				filterBy: false,
			},
			{
				id: 'bidirectional',
				type: 'boolean',
				label: __('Bidirectional', 'wp-content-connect'),
				render: ({ item }) => (
					<>
						{item.bidirectional
							? __('Yes', 'wp-content-connect')
							: __('No', 'wp-content-connect')}
					</>
				),
				filterBy: false,
			},
			{
				id: 'source',
				type: 'text',
				label: __('Source', 'wp-content-connect'),
				elements: [
					{ value: 'custom', label: __('Custom', 'wp-content-connect') },
					{ value: 'code', label: __('Code', 'wp-content-connect') },
				],
				filterBy: { operators: ['is'] },
			},
			{
				id: 'status',
				type: 'text',
				label: __('Status', 'wp-content-connect'),
				elements: [
					{ value: 'active', label: __('Active', 'wp-content-connect') },
					{ value: 'disabled', label: __('Disabled', 'wp-content-connect') },
					{ value: 'error', label: __('Inactive', 'wp-content-connect') },
				],
				render: ({ item }) => {
					switch (item.status) {
						case 'error':
							return (
								<Tooltip text={item.error ?? ''}>
									<span>{__('Inactive', 'wp-content-connect')}</span>
								</Tooltip>
							);
						case 'disabled':
							return <>{__('Disabled', 'wp-content-connect')}</>;
						default:
							return <>{__('Active', 'wp-content-connect')}</>;
					}
				},
				filterBy: { operators: ['is'] },
			},
		],
		[postTypes],
	);

	const setStatus = async (items: Row[], status: 'publish' | 'draft') => {
		try {
			await Promise.all(
				items.map((item) =>
					saveEntityRecord(
						'postType',
						RELATIONSHIP_POST_TYPE,
						{ id: item.postId, status },
						{ throwOnError: true },
					),
				),
			);

			createSuccessNotice(
				status === 'publish'
					? __('Relationship enabled.', 'wp-content-connect')
					: __('Relationship disabled.', 'wp-content-connect'),
				{ type: 'snackbar' },
			);
		} catch (error) {
			createErrorNotice(
				(error as { message?: string })?.message ??
					__('The relationship could not be updated.', 'wp-content-connect'),
				{ type: 'snackbar' },
			);
		}
	};

	const actions: Action<Row>[] = [
		{
			id: 'edit',
			label: __('Edit', 'wp-content-connect'),
			icon: pencil,
			isPrimary: true,
			isEligible: isCustom,
			callback: (items) => onEdit(items[0]),
		},
		{
			id: 'enable',
			label: __('Enable', 'wp-content-connect'),
			isEligible: (item) => isCustom(item) && item.status === 'disabled',
			supportsBulk: true,
			callback: (items) => setStatus(items, 'publish'),
		},
		{
			id: 'disable',
			label: __('Disable', 'wp-content-connect'),
			isEligible: (item) => isCustom(item) && item.status !== 'disabled',
			supportsBulk: true,
			callback: (items) => setStatus(items, 'draft'),
		},
		{
			id: 'duplicate',
			label: __('Duplicate', 'wp-content-connect'),
			icon: copy,
			callback: (items) => onDuplicate(items[0]),
		},
		{
			id: 'copy-php',
			label: __('Copy as PHP', 'wp-content-connect'),
			icon: code,
			modalHeader: __('Copy as PHP', 'wp-content-connect'),
			modalSize: 'large',
			RenderModal: CopyAsPhpModal,
		},
		{
			id: 'delete',
			label: __('Delete', 'wp-content-connect'),
			icon: trash,
			isEligible: isCustom,
			supportsBulk: true,
			modalHeader: __('Delete relationship', 'wp-content-connect'),
			RenderModal: DeleteModal,
		},
	];

	const { data, paginationInfo } = useMemo(
		() => filterSortAndPaginate(rows, view, fields),
		[rows, view, fields],
	);

	return (
		<DataViews<Row>
			data={data}
			fields={fields}
			view={view}
			onChangeView={setView}
			actions={actions}
			paginationInfo={paginationInfo}
			defaultLayouts={DEFAULT_LAYOUTS}
			getItemId={(item) => item.id}
			isItemClickable={isCustom}
			onClickItem={onEdit}
			isLoading={isLoading}
			searchLabel={__('Search relationships', 'wp-content-connect')}
			empty={<p>{__('No relationships yet.', 'wp-content-connect')}</p>}
		/>
	);
}
