/**
 * External dependencies
 */
import React from 'react';
import { ContentPicker } from '@10up/block-components';
import type { WP_REST_API_Search_Result, WP_REST_API_User } from 'wp-types';

/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { addQueryArgs } from '@wordpress/url';
import type { Post, User } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { store } from '../../store';
import { ContentConnectRelationship } from '../../store/types';

/**
 * Normalized suggestion type for search results.
 */
type NormalizedSuggestion = {
	id: number;
	subtype: string;
	title: string;
	type: string;
	url: string;
};

/**
 * Picked item type.
 */
type PickedItemType = {
	id: number;
	type: string;
	uuid: string;
	title: string;
	url?: string;
};

/**
 * Search result filter function type.
 */
type SearchResultFilter = (
	item: NormalizedSuggestion,
	originalResult: WP_REST_API_Search_Result | WP_REST_API_User
) => NormalizedSuggestion;

/**
 * Picked item filter function type.
 */
type PickedItemFilter = (
	item: Partial<PickedItemType>,
	originalResult: Post | Term | User
) => Partial<PickedItemType>;

/**
 * Picked item preview component type.
 */
type PickedItemPreviewComponent = React.ComponentType<{ item: PickedItemType }>;

type RelationshipManagerProps = {
	postId: number | null;
	relationship: ContentConnectRelationship;
};

type FilterContext = {
	rel_key: string;
	rel_type: string;
	postId: number | null;
	mode: 'post' | 'user' | 'term';
};

/**
 * Default search result filter.
 */
const defaultSearchResultFilter: SearchResultFilter = (item: NormalizedSuggestion) => {
	return item;
};

/**
 * Default picked item filter.
 */
const defaultPickedItemFilter: PickedItemFilter = (item: Partial<PickedItemType>) => {
	return item;
};

export function RelationshipManager({ postId, relationship }: RelationshipManagerProps) {
	const { updateRelatedEntities } = useDispatch(store);

	const { relatedEntities } = useSelect((select) => ({
		relatedEntities: select(store).getRelatedEntities(postId, {
			rel_key: relationship.rel_key,
			rel_type: relationship.rel_type,
		}),
	}), [postId, relationship.rel_key, relationship.rel_type]);

	const handleChange = async (newEntities: PickedItemType[]) => {
		await updateRelatedEntities(
			postId,
			relationship.rel_key,
			relationship.rel_type,
			newEntities
		);
	};

	const mode = (relationship?.object_type ?? 'post') as 'post' | 'user' | 'term';

	const filterContext: FilterContext = useMemo(
		() => ({
			rel_key: relationship.rel_key,
			rel_type: relationship.rel_type,
			postId,
			mode,
		}),
		[relationship.rel_key, relationship.rel_type, postId, mode]
	);

	const searchResultFilter = useMemo(() => {
		return applyFilters(
			'contentConnect.searchResultFilter',
			defaultSearchResultFilter,
			filterContext
		) as SearchResultFilter;
	}, [filterContext]);

	const pickedItemFilter = useMemo(() => {
		return applyFilters(
			'contentConnect.pickedItemFilter',
			defaultPickedItemFilter,
			filterContext
		) as PickedItemFilter;
	}, [filterContext]);

	const pickedItemPreviewComponent = useMemo(() => {
		return applyFilters(
			'contentConnect.pickedItemPreviewComponent',
			undefined,
			filterContext
		) as PickedItemPreviewComponent | undefined;
	}, [filterContext]);

	return (
		<div className={`content-connect-relationship-manager content-connect-relationship-manager-${relationship.rel_name} content-connect-relationship-manager-${relationship.rel_key}`}>
			<ContentPicker
				onPickChange={handleChange}
				mode={relationship?.object_type ?? 'post'}
				content={relatedEntities}
				contentTypes={relationship?.post_type}
				maxContentItems={relationship?.max_items ?? 100}
				isOrderable={relationship?.sortable ?? false}
				queryFilter={(query) => {
					if (relationship?.rel_key) {
						return addQueryArgs(query, {
							content_connect: relationship.rel_key
						});
					}
					return query;
				}}
				searchResultFilter={searchResultFilter}
				pickedItemFilter={pickedItemFilter}
				PickedItemPreviewComponent={pickedItemPreviewComponent}
			/>
		</div>
	);
}
