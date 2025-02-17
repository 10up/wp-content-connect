import { createReduxStore, register, select as wpSelect, dispatch } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';
import * as api from './api';
import { ContentConnectRelatedPosts, ContentConnectRelationships, ContentConnectState } from './types';

export const STORE_NAME = 'wp-content-connect';

/**
 * Store defaults
 */
const DEFAULT_STATE: ContentConnectState = {
	relationships: {},
	relatedPosts: {},
	dirtyPostIds: new Set(),
};

type Action = {
	type: string;
	postId?: number;
	relationships?: ContentConnectRelationships;
	relatedPosts?: ContentConnectRelatedPosts;
	key?: string;
};

const actions = {
	setRelationships(postId: number, relationships: ContentConnectRelationships) {
		return {
			type: 'SET_RELATIONSHIPS',
			postId,
			relationships,
		};
	},
	setRelatedPosts(key: string, relatedPosts: ContentConnectRelatedPosts) {
		return {
			type: 'SET_RELATED_POSTS',
			key,
			relatedPosts,
		};
	},
	markPostAsDirty(postId: number) {
		return {
			type: 'MARK_POST_AS_DIRTY',
			postId,
		};
	},
	clearDirtyPosts() {
		return {
			type: 'CLEAR_DIRTY_POSTS',
		};
	},
	updateRelatedPosts(postId: number, relKey: string, relatedIds: number[]) {
		return async function thunk({dispatch}) {
			const key = `related-${postId}-${relKey}`;

			await api.updateRelatedPosts(
				postId,
				{ rel_key: relKey, related_ids: relatedIds },
				{ related_ids: relatedIds }
			);

			// Fetch the updated posts to ensure our state is in sync
			const updatedPosts = await api.getRelatedPosts(postId, { rel_key: relKey });
			dispatch.setRelatedPosts(key, updatedPosts);
			dispatch.markPostAsDirty(postId);
		};
	},
};

export const store = createReduxStore(STORE_NAME, {
	reducer(state = DEFAULT_STATE, action: Action) {
		switch (action.type) {
			case 'SET_RELATIONSHIPS':
				if (!action.postId || !action.relationships) {
					return state;
				}
				return {
					...state,
					relationships: {
						...state.relationships,
						[action.postId]: action.relationships,
					},
				};

			case 'SET_RELATED_POSTS':
				if (!action.key || !action.relatedPosts) {
					return state;
				}
				return {
					...state,
					relatedPosts: {
						...state.relatedPosts,
						[action.key]: action.relatedPosts,
					},
				};

			case 'MARK_POST_AS_DIRTY':
				if (!action.postId) {
					return state;
				}
				const newDirtyPostIds = new Set(state.dirtyPostIds);
				newDirtyPostIds.add(action.postId);
				return {
					...state,
					dirtyPostIds: newDirtyPostIds,
				};

			case 'CLEAR_DIRTY_POSTS':
				return {
					...state,
					dirtyPostIds: new Set(),
				};
		}

		return state;
	},
	actions,
	selectors: {
		getRelationships(state: ContentConnectState, postId: number) {
			return state.relationships[postId] || {};
		},
		getRelatedPosts(state: ContentConnectState, postId: number, relKey: string) {
			const key = `related-${postId}-${relKey}`;
			return state.relatedPosts[key] || [];
		},
		getDirtyPostIds(state: ContentConnectState) {
			return Array.from(state.dirtyPostIds);
		},
	},
	resolvers: {
		getRelationships: (postId: number) => async function thunk({dispatch}) {
			const relationships = await api.getRelationships(postId);
			dispatch.setRelationships(postId, relationships);
		},
		getRelatedPosts: (postId: number, relKey: string) => async function thunk({dispatch}) {
			const key = `related-${postId}-${relKey}`;
			const relatedPosts = await api.getRelatedPosts(postId, { rel_key: relKey });
			dispatch.setRelatedPosts(key, relatedPosts);
		},
	},
});

register(store);

async function persistContentConnectionChanges() {
	const dirtyPostIds = wpSelect(STORE_NAME).getDirtyPostIds();

	// Process each dirty post
	await Promise.all(
		dirtyPostIds.map(async (postId) => {
			const relationships = wpSelect(STORE_NAME).getRelationships(postId);

			// Update each relationship for the post
			await Promise.all(
				Object.entries(relationships).map(async ([relKey, relationship]) => {
					const relatedPosts = wpSelect(STORE_NAME).getRelatedPosts(
						`related-${postId}-${relKey}`
					);

					await api.updateRelatedPosts(
						postId,
						{
							rel_key: relKey,
							related_ids: relatedPosts.map(post => post.ID),
						},
						{
							related_ids: relatedPosts.map(post => post.ID),
						}
					);
				})
			);
		})
	);

	// Clear dirty posts after successful save
	dispatch(STORE_NAME).clearDirtyPosts();
}

// Add the pre-save hook to persist changes
addFilter(
	'editor.preSavePost',
	'wp-content-connect/persist-connections',
	async (edits, options: { readonly isAutosave: boolean; readonly isPreview: boolean }) => {
		try {
			if (!options.isAutosave && !options.isPreview) {
				await persistContentConnectionChanges();
			}
			return edits;
		} catch (error) {
			console.error('Failed to persist content connections:', error);
			return edits;
		}
	}
);
