import React from 'react';
import { useSelect } from '@wordpress/data';
import { store as editorStore, PluginDocumentSettingPanel } from '@wordpress/editor';
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

	const enabledRelationships = Object.values(relationships).filter(
		(relationship) => relationship.enable_ui === true
	);

	if (enabledRelationships.length === 0) {
		return null;
	}

	return (
		<>
			{enabledRelationships.map((relationship) => (
				<PluginDocumentSettingPanel
					name={`content-connect-relationship-${relationship.rel_key}`}
					title={relationship.labels.name}
				>
					<RelationshipManager
						postId={postId}
						relationship={relationship}
					/>
				</PluginDocumentSettingPanel>
			))}
		</>
	);
}
