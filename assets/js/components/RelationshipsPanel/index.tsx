/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';

/**
 * Internal dependencies
 */
import { store } from '../../store';
import { RelationshipManager } from '../RelationshipManager';

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
			{enabledRelationships.map((relationship) => {
				const safeRelKey = relationship.rel_key.replace(/[^a-z0-9_-]/gi, '-');
				return (
					<PluginDocumentSettingPanel
						key={relationship.rel_key}
						name={`content-connect-relationship-${safeRelKey}`}
						title={relationship.labels.name}
					>
						<RelationshipManager
							postId={postId}
							relationship={relationship}
						/>
					</PluginDocumentSettingPanel>
				);
			})}
		</>
	);
}
