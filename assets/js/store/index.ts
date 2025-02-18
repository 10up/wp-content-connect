import { createReduxStore, register, select, dispatch } from '@wordpress/data';
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

type SetRelationshipsAction = {
	type: 'SET_RELATIONSHIPS';
	postId: number;
	relationships: ContentConnectRelationships;
};

type SetRelatedPostsAction = {
	type: 'SET_RELATED_POSTS';
	key: string;
	relatedPosts: ContentConnectRelatedPosts;
};

type MarkPostAsDirtyAction = {
	type: 'MARK_POST_AS_DIRTY';
	postId: number;
};

type ClearDirtyPostsAction = {
	type: 'CLEAR_DIRTY_POSTS';
};

type Action =
	| SetRelationshipsAction
	| SetRelatedPostsAction
	| MarkPostAsDirtyAction
	| ClearDirtyPostsAction;

const actions = {
	setRelationships(postId: number, relationships: ContentConnectRelationships): SetRelationshipsAction {
		return {
			type: 'SET_RELATIONSHIPS',
			postId,
			relationships,
		};
	},
	setRelatedPosts(key: string, relatedPosts: ContentConnectRelatedPosts): SetRelatedPostsAction {
		return {
			type: 'SET_RELATED_POSTS',
			key,
			relatedPosts,
		};
	},
	markPostAsDirty(postId: number): MarkPostAsDirtyAction {
		return {
			type: 'MARK_POST_AS_DIRTY',
			postId,
		};
	},
	clearDirtyPosts(): ClearDirtyPostsAction {
		return {
			type: 'CLEAR_DIRTY_POSTS',
		};
	},
	updateRelatedPosts(postId: number, relKey: string, relatedIds: number[]) {
		return async function thunk({dispatch}) {
			await api.updateRelatedPosts(
				postId,
				relKey,
				relatedIds
			);

			dispatch.invalidateResolutionForStoreSelector('getRelatedPosts');
			dispatch.markPostAsDirty(postId);
		};
	},
};

export const store = createReduxStore(STORE_NAME, {
	reducer(state: ContentConnectState = DEFAULT_STATE, action: Action) {
		switch (action.type) {
			case 'SET_RELATIONSHIPS':
				return {
					...state,
					relationships: {
						...state.relationships,
						[action.postId]: action.relationships,
					},
				};

			case 'SET_RELATED_POSTS':
				return {
					...state,
					relatedPosts: {
						...state.relatedPosts,
						[action.key]: action.relatedPosts,
					},
				};

			case 'MARK_POST_AS_DIRTY': {
				const dirtyPostIds = new Set(state.dirtyPostIds);
				dirtyPostIds.add(action.postId);
				return {
					...state,
					dirtyPostIds,
				};
			}

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
		getRelationships(state: ContentConnectState, postId: number, options?: api.GetRelationshipsOptions) {
			return state.relationships[postId] || {};
		},
		getRelatedPosts(state: ContentConnectState, postId: number, options: api.GetRelatedPostsOptions) {
			const key = `related-${postId}-${options.rel_key}`;
			return state.relatedPosts[key] || [];
		},
		getDirtyPostIds(state: ContentConnectState) {
			return Array.from(state.dirtyPostIds);
		},
	},
	resolvers: {
		getRelationships: (postId: number, options?: api.GetRelationshipsOptions) => async function thunk({dispatch}) {
			const relationships = await api.getRelationships(postId, options);
			dispatch.setRelationships(postId, relationships);
		},
		getRelatedPosts: (postId: number, options: api.GetRelatedPostsOptions) => async function thunk({dispatch}) {
			const key = `related-${postId}-${options.rel_key}`;
			const relatedPosts = await api.getRelatedPosts(postId, options);
			dispatch.setRelatedPosts(key, relatedPosts);
		},
	},
});

register(store);

async function persistContentConnectionChanges() {
	const dirtyPostIds = select(STORE_NAME).getDirtyPostIds();

	// Process each dirty post
	await Promise.all(
		dirtyPostIds.map(async (postId) => {
			const relationships = select(STORE_NAME).getRelationships(postId);

			// Update each relationship for the post
			await Promise.all(
				Object.entries(relationships).map(async ([relKey, relationship]) => {
					const relatedPosts = select(STORE_NAME).getRelatedPosts(postId, {
						rel_key: relKey,
					});

					await api.updateRelatedPosts(
						postId,
						relKey,
						relatedPosts.map(post => post.ID),
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
