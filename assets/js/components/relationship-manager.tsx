import React from 'react';
import { ContentPicker } from '@10up/block-components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store } from '../store';
import { ContentConnectRelationship } from '../store/types';

type RelationshipManagerProps = {
	postId: number | null;
	relationship: ContentConnectRelationship;
};

export function RelationshipManager({ postId, relationship }: RelationshipManagerProps) {
	const { updateRelatedPosts } = useDispatch(store);

	const { relatedEntities } = useSelect((select) => ({
		relatedEntities: select(store).getRelatedPosts(postId, {
			rel_key: relationship.rel_key,
		}),
	}), [postId, relationship.rel_key, relationship.post_type]);

	const handleChange = async (newEntities: any[]) => {
		console.log(newEntities);
		const newIds = newEntities.map(entity => entity.id);
		updateRelatedPosts(postId, relationship.rel_key, newIds);
	};

	return (
		<ContentPicker
			onPickChange={handleChange}
			mode={relationship?.object_type ?? 'post'}
			content={relatedEntities}
			contentTypes={relationship.post_type}
			maxContentItems={relationship?.max_items ?? 100}
			isOrderable={relationship?.sortable ?? false}
		/>
	);
}
