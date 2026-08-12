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
import { store, persistContentConnectChanges, getRelationshipsKey } from './store';
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
				/>,
			);
		}
	});

	// Initialize relationships in the store
	if (postId) {
		const relationships: Record<string, ContentConnectRelationship> = {};
		Object.values(relationshipsMap).forEach((rel) => {
			relationships[rel.rel_key] = rel;
		});
		// Key must match what the store selectors/persist layer compose from the
		// post ID, otherwise persistContentConnectChanges() can't find them on save.
		dispatch(store).setRelationships(getRelationshipsKey(postId), relationships);
	}

	// Set while we are intentionally saving so the beforeunload guard below does
	// not warn on the navigation our own submit triggers.
	let isSaving = false;

	// Warn before leaving with unsaved Content Connect changes. The block editor
	// gets this for free by dirtying the editor store (_content_connect_edit_lock);
	// the classic screen has no such store, so we mirror it with a beforeunload
	// guard driven by the same dirty state.
	window.addEventListener('beforeunload', (event) => {
		if (isSaving) {
			return;
		}

		if (select(store).getDirtyEntityIds().length === 0) {
			return;
		}

		// Triggers the browser's native "Leave site? Changes you made may not be
		// saved." prompt. The returnValue text is ignored by modern browsers.
		event.preventDefault();
		event.returnValue = '';
	});

	// Hook into form submission to persist relationships before save
	postForm.addEventListener('submit', async (event) => {
		const dirtyEntityIds = select(store).getDirtyEntityIds();

		// Only intercept if there are unsaved relationship changes
		if (dirtyEntityIds.length > 0) {
			event.preventDefault();
			event.stopPropagation();

			// Saving now: let the post-persist form submit navigate without the
			// beforeunload guard prompting.
			isSaving = true;

			try {
				await persistContentConnectChanges();
				postForm.submit();
			} catch (error) {
				console.error('Failed to persist Content Connect changes:', error); // eslint-disable-line no-console
				postForm.submit();
			}
		}
	});
};

domReady(registerPanels);
