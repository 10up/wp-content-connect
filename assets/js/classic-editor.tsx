/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import { select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { RelationshipManager } from './components/RelationshipManager';
import { store, persistContentConnectChanges } from './store';
import { ContentConnectRelationship } from './store/types';

/**
 * Register the relationships panels.
 */
const registerPanels = () => {
	const containers = document.querySelectorAll<HTMLElement>('[data-content-connect]');
	if (!containers.length) {
		return;
	}

	const postForm = document.querySelector<HTMLFormElement>('#post');
	if (!postForm) {
		return;
	}

	let postId: number | null = null;
	const relationshipsMap: Record<string, ContentConnectRelationship> = {};

	containers.forEach((container) => {
		const { postId: containerPostId, relationship: relationshipJson } = container.dataset;

		let relationshipData: ContentConnectRelationship | false = false;
		try {
			relationshipData = JSON.parse(relationshipJson || '') as ContentConnectRelationship;
		} catch (e) {
			console.error('Invalid JSON in data-content-connect:', e);
			return;
		}

		if (container && relationshipData) {
			postId = parseInt(containerPostId ?? '0', 10);
			relationshipsMap[relationshipData.rel_key] = relationshipData;

			const root = createRoot(container);
			root.render(
				<RelationshipManager
					key={relationshipData.rel_key}
					postId={postId}
					relationship={relationshipData}
				/>
			);
		}
	});

	// Initialize relationships in the store
	if (postId) {
		const relationships: Record<string, ContentConnectRelationship> = {};
		Object.values(relationshipsMap).forEach((rel) => {
			relationships[rel.rel_key] = rel;
		});
		dispatch(store).setRelationships(postId, relationships);
	}

	// Hook into form submission to persist relationships before save
	postForm.addEventListener('submit', async (event) => {
		const dirtyEntityIds = select(store).getDirtyEntityIds();
		console.log('dirtyEntityIds', dirtyEntityIds);

		// Only intercept if there are unsaved relationship changes
		if (dirtyEntityIds.length > 0) {
			event.preventDefault();
			event.stopPropagation();

			try {
				await persistContentConnectChanges();
				postForm.submit();
			} catch (error) {
				console.error('Failed to persist Content Connect changes:', error); // eslint-disable-line no-console
				postForm.submit();
			}
		}
	});
}

domReady(registerPanels);
