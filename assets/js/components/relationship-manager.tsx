import React from 'react';
import { ContentPicker } from '@10up/block-components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import { store } from '../store';
import { ContentConnectRelationship } from '../store/types';

type RelationshipManagerProps = {
	postId: number | null;
	relationship: ContentConnectRelationship;
};

export function RelationshipManager({ postId, relationship }: RelationshipManagerProps) {
	const { updateRelatedEntities } = useDispatch(store);

	const { relatedEntities } = useSelect((select) => ({
		relatedEntities: select(store).getRelatedEntities(postId, {
			rel_key: relationship.rel_key,
			rel_type: relationship.rel_type,
		}),
	}), [postId, relationship.rel_key]);

	const handleChange = async (newEntities: any[]) => {
		await updateRelatedEntities(
			postId,
			relationship.rel_key,
			relationship.rel_type,
			newEntities
		);
	};

	const searchResultFilter = useCallback((item, result) => {
		return {...item, url: '' };
	}, []);

	const pickedItemFilter = useCallback((item) => {
		return {...item, url: '' };
	}, []);

	return (
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
		/>
	);
}
