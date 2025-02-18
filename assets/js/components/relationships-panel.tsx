import React from 'react';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store } from '../store';
import { RelationshipManager } from './relationship-manager';

export function RelationshipsPanel() {
	const { postId, relationships } = useSelect((select) => {
		const postId = select(editorStore).getCurrentPostId();
		const relationships = select(store).getRelationships(postId);

		return {
			postId,
			relationships,
		};
	}, []);

	if (!relationships || Object.keys(relationships).length === 0) {
		return null;
	}

	return (
		<>
			{Object.values(relationships).map((relationship) => (
				<RelationshipManager
					key={relationship.rel_key}
					postId={postId}
					relationship={relationship}
				/>
			))}
		</>
	);
}
