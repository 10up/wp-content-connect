import { createReduxStore, register, select, dispatch, createSelector } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';
import * as api from './api';
import { ContentConnectRelatedEntities, ContentConnectRelationships, ContentConnectState } from './types';

export const STORE_NAME = 'wp-content-connect';

/**
 * Generates a unique key for related entities based on post ID and relationship key.
 *
 * @param postId The ID of the post.
 * @param relKey The key of the relationship.
 * @returns A unique key for the related entities.
 */
function getRelatedEntitiesKey(postId: number, relKey: string): string {
	return `related-${postId}-${relKey}`;
}

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
	/**
	 * Sets the relationships for a given post ID.
	 *
	 * @param postId The ID of the post to set relationships for.
	 * @param relationships The relationships to set.
	 * @returns The action to set the relationships.
	 */
	setRelationships(postId: number, relationships: ContentConnectRelationships): SetRelationshipsAction {
		return {
			type: 'SET_RELATIONSHIPS',
			postId,
			relationships,
		};
	},
	/**
	 * Sets the related entities for a given key.
	 *
	 * @param key The key for the related entities.
	 * @param relatedEntities The related entities to set.
	 * @returns The action to set the related entities.
	 */
	setRelatedEntities(key: string, relatedEntities: ContentConnectRelatedEntities): SetRelatedEntitiesAction {
		return {
			type: 'SET_RELATED_ENTITIES',
			key,
			relatedEntities,
		};
	},
	/**
	 * Marks a post as dirty, indicating that it has unsaved changes.
	 *
	 * @param postId The ID of the post to mark as dirty.
	 * @returns The action to mark the post as dirty.
	 */
	markPostAsDirty(postId: number): MarkPostAsDirtyAction {
		return {
			type: 'MARK_POST_AS_DIRTY',
			postId,
		};
	},
	/**
	 * Clears the dirty entities in the store.
	 *
	 * @returns The action to clear dirty entities.
	 */
	clearDirtyEntities(): ClearDirtyEntitiesAction {
		return {
			type: 'CLEAR_DIRTY_ENTITIES',
		};
	},
	/**
	 * Updates the related entities for a given post and relationship.
	 *
	 * @param postId The ID of the post to update.
	 * @param relKey The key of the relationship.
	 * @param relType The type of the relationship.
	 * @param relatedIds The IDs of the related entities.
	 */
	updateRelatedEntities(
		postId: number | null,
		relKey: string,
		relType: string,
		entities: ContentConnectRelatedEntities
	) {
		return async function thunk({dispatch, select}) {
			if (postId === null) {
				return;
			}

			const key = getRelatedEntitiesKey(postId, relKey);

			const relatedIds = entities.map(entity => {
				return typeof entity.id === 'string' ? parseInt(entity.id, 10) : entity.id;
			});

			dispatch.setRelatedEntities(key, entities);

			await api.updateRelatedEntities(
				postId,
				relKey,
				relType,
				relatedIds
			);

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
		getRelatedEntities: createSelector(
			(state: ContentConnectState, postId: number | null, options: api.GetRelatedEntitiesOptions): ContentConnectRelatedEntities => {
				if (postId === null || !options?.rel_key) {
					return [];
				}
				const key = getRelatedEntitiesKey(postId, options.rel_key);
				return state.relatedEntities[key] || [];
			},
			(state, postId, options) => {
				if (postId === null || !options?.rel_key) {
					return ['empty'];
				}
				const key = getRelatedEntitiesKey(postId, options.rel_key);
				return [state.relatedEntities[key]];
			}
		),
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
			const key = getRelatedEntitiesKey(postId, options.rel_key);
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
				Object.entries(relationships).map(async ([relKey, relType]) => {
					const relatedEntities = select(STORE_NAME).getRelatedEntities(postId, {
						rel_key: relKey,
						rel_type: relType,
					});

					await api.updateRelatedEntities(
						postId,
						relKey,
						relType as string,
						relatedEntities.map(post => post.id),
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
