import { createReduxStore, register, select, dispatch } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';
import * as api from './api';
import { ContentConnectRelatedEntities, ContentConnectRelationships, ContentConnectState } from './types';

export const STORE_NAME = 'wp-content-connect';

/**
 * Store defaults
 */
const DEFAULT_STATE: ContentConnectState = {
	relationships: {},
	relatedEntities: {},
	dirtyEntityIds: new Set(),
};

type SetRelationshipsAction = {
	type: 'SET_RELATIONSHIPS';
	postId: number;
	relationships: ContentConnectRelationships;
};

type SetRelatedEntitiesAction = {
	type: 'SET_RELATED_ENTITIES';
	key: string;
	relatedEntities: ContentConnectRelatedEntities;
};

type MarkPostAsDirtyAction = {
	type: 'MARK_POST_AS_DIRTY';
	postId: number;
};

type ClearDirtyEntitiesAction = {
	type: 'CLEAR_DIRTY_ENTITIES';
};

type Action =
	| SetRelationshipsAction
	| SetRelatedEntitiesAction
	| MarkPostAsDirtyAction
	| ClearDirtyEntitiesAction;

const actions = {
	setRelationships(postId: number, relationships: ContentConnectRelationships): SetRelationshipsAction {
		return {
			type: 'SET_RELATIONSHIPS',
			postId,
			relationships,
		};
	},
	setRelatedEntities(key: string, relatedEntities: ContentConnectRelatedEntities): SetRelatedEntitiesAction {
		return {
			type: 'SET_RELATED_ENTITIES',
			key,
			relatedEntities,
		};
	},
	markPostAsDirty(postId: number): MarkPostAsDirtyAction {
		return {
			type: 'MARK_POST_AS_DIRTY',
			postId,
		};
	},
	clearDirtyEntities(): ClearDirtyEntitiesAction {
		return {
			type: 'CLEAR_DIRTY_ENTITIES',
		};
	},
	updateRelatedEntities(postId: number | null, relKey: string, relType: string, relatedIds: number[]) {
		return async function thunk({dispatch}) {
			if (postId === null) {
				return;
			}

			await api.updateRelatedEntities(
				postId,
				relKey,
				relType,
				relatedIds
			);

			dispatch.invalidateResolutionForStoreSelector('getRelatedEntities');
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

			case 'SET_RELATED_ENTITIES':
				return {
					...state,
					relatedEntities: {
						...state.relatedEntities,
						[action.key]: action.relatedEntities,
					},
				};

			case 'MARK_POST_AS_DIRTY': {
				const dirtyEntityIds = new Set(state.dirtyEntityIds);
				dirtyEntityIds.add(action.postId);
				return {
					...state,
					dirtyEntityIds,
				};
			}

			case 'CLEAR_DIRTY_ENTITIES':
				return {
					...state,
					dirtyEntityIds: new Set(),
				};
		}

		return state;
	},
	actions,
	selectors: {
		getRelationships(state: ContentConnectState, postId: number | null, options?: api.GetRelationshipsOptions) {
			if (postId === null) {
				return {};
			}
			return state.relationships[postId] || {};
		},
		getRelatedEntities(state: ContentConnectState, postId: number | null, options: api.GetRelatedEntitiesOptions) {
			if (postId === null) {
				return [];
			}
			const key = `related-${postId}-${options.rel_key}`;
			return state.relatedEntities[key] || [];
		},
		getDirtyEntityIds(state: ContentConnectState) {
			return Array.from(state.dirtyEntityIds);
		},
	},
	resolvers: {
		getRelationships: (postId: number, options?: api.GetRelationshipsOptions) => async function thunk({dispatch}) {
			const relationships = await api.getRelationships(postId, options);
			dispatch.setRelationships(postId, relationships);
		},
		getRelatedEntities: (postId: number, options: api.GetRelatedEntitiesOptions) => async function thunk({dispatch}) {
			const key = `related-${postId}-${options.rel_key}`;
			const relatedEntities = await api.getRelatedEntities(postId, options);
			dispatch.setRelatedEntities(key, relatedEntities);
		},
	},
});

register(store);

async function persistContentConnectionChanges() {
	const dirtyEntityIds = select(STORE_NAME).getDirtyEntityIds();

	// Process each dirty post
	await Promise.all(
		dirtyEntityIds.map(async (postId) => {
			const relationships = select(STORE_NAME).getRelationships(postId);

			// Update each relationship for the post
			await Promise.all(
				Object.entries(relationships).map(async ([relKey, relType, relationship]) => {
					const relatedEntities = select(STORE_NAME).getRelatedEntities(postId, {
						rel_key: relKey,
						rel_type: relType,
					});

					await api.updateRelatedEntities(
						postId,
						relKey,
						relType,
						relatedEntities.map(post => post.ID),
					);
				})
			);
		})
	);

	// Clear dirty entities after successful save
	dispatch(STORE_NAME).clearDirtyEntities();
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
