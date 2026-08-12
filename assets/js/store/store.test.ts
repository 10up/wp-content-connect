/**
 * @jest-environment node
 */
import { createReduxStore, register, select, dispatch } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';
import * as api from './api';

jest.mock('@wordpress/api-fetch');
jest.mock('@wordpress/hooks', () => ({ addFilter: jest.fn() }));
jest.mock('@wordpress/editor', () => ({ store: 'core/editor-mock' }));
jest.mock('./api');

const editPostSpy = jest.fn();

// Minimal fake editor store so markPostAsDirty's editor calls resolve.
const editorMock = createReduxStore('core/editor-mock', {
	reducer: (state = { meta: { existing_meta: 'keep' } }) => state,
	selectors: {
		getEditedPostAttribute: (state: { meta: Record<string, unknown> }) => state.meta,
		getCurrentPostType: () => 'post',
	},
	actions: {
		editPost(edits: unknown) {
			editPostSpy(edits);
			return { type: 'EDIT_POST' };
		},
	},
});
register(editorMock);

// Importing the module registers the wp-content-connect store on the default registry.
// eslint-disable-next-line import/first
import { STORE_NAME, persistContentConnectChanges } from './index';

const mockApi = api as jest.Mocked<typeof api>;

// Capture the pre-save filter callback registered at module load, before any
// beforeEach mock-clearing wipes addFilter's recorded calls.
// Typed as jest.Mock purely because it is a named callable type; the stored
// value is the real (plain) callback registered by the store module.
const preSaveCallback = (addFilter as jest.Mock).mock.calls.find(
	(args) => args[0] === 'editor.preSavePost',
)?.[2] as jest.Mock;

beforeEach(() => {
	editPostSpy.mockClear();
	jest.clearAllMocks();
	jest.spyOn(console, 'error').mockImplementation(() => {});
	dispatch(STORE_NAME).clearDirtyEntities();
});

afterEach(() => {
	(console.error as jest.Mock).mockRestore();
});

describe('dirty entity tracking', () => {
	it('collects unique dirty post IDs', () => {
		dispatch(STORE_NAME).markPostAsDirty(1);
		dispatch(STORE_NAME).markPostAsDirty(2);
		dispatch(STORE_NAME).markPostAsDirty(1);

		expect(select(STORE_NAME).getDirtyEntityIds()).toEqual([1, 2]);
	});

	it('clears dirty entities', () => {
		dispatch(STORE_NAME).markPostAsDirty(5);
		dispatch(STORE_NAME).clearDirtyEntities();

		expect(select(STORE_NAME).getDirtyEntityIds()).toEqual([]);
	});
});

describe('markPostAsDirty editor integration', () => {
	it('edits post meta with a lock while preserving existing meta', () => {
		dispatch(STORE_NAME).markPostAsDirty(1);

		expect(editPostSpy).toHaveBeenCalledTimes(1);
		const edits = editPostSpy.mock.calls[0][0] as { meta: Record<string, unknown> };
		expect(edits.meta.existing_meta).toBe('keep');
		expect(typeof edits.meta._content_connect_edit_lock).toBe('number');
	});
});

describe('relationships selector', () => {
	it('returns an empty object for a null post ID', () => {
		expect(select(STORE_NAME).getRelationships(null)).toEqual({});
	});

	it('returns stored relationships keyed by post ID and options', () => {
		const relationships = { basic: { rel_key: 'basic', rel_type: 'post-to-post' } };
		dispatch(STORE_NAME).setRelationships('relationships-5-{}', relationships);

		expect(select(STORE_NAME).getRelationships(5)).toEqual(relationships);
	});

	it('does not mix cache entries that use different options', () => {
		const filtered = { car: { rel_key: 'car', rel_type: 'post-to-post' } };
		dispatch(STORE_NAME).setRelationships(
			`relationships-6-${JSON.stringify({ rel_type: 'post-to-post' })}`,
			filtered,
		);

		// Same post, no options: should be empty, not the filtered payload.
		expect(select(STORE_NAME).getRelationships(6)).toEqual({});
		expect(select(STORE_NAME).getRelationships(6, { rel_type: 'post-to-post' })).toEqual(
			filtered,
		);
	});
});

describe('relatedEntities selector', () => {
	it('returns an empty array for a null post ID or missing rel_key', () => {
		expect(
			select(STORE_NAME).getRelatedEntities(null, { rel_key: 'k', rel_type: 'post-to-post' }),
		).toEqual([]);
		// @ts-expect-error - exercising the missing rel_key guard
		expect(select(STORE_NAME).getRelatedEntities(5, {})).toEqual([]);
	});

	it('returns stored related entities keyed by post ID and rel_key', () => {
		const entities = [{ id: 11, name: 'a', type: 'post', uuid: 'u1' }];
		dispatch(STORE_NAME).setRelatedEntities('related-5-basic', entities);

		expect(
			select(STORE_NAME).getRelatedEntities(5, {
				rel_key: 'basic',
				rel_type: 'post-to-post',
			}),
		).toEqual(entities);
	});
});

describe('updateRelatedEntities thunk', () => {
	it('stores the entities and marks the post dirty', async () => {
		const entities = [{ id: 11, name: 'a', type: 'post', uuid: 'u1' }];

		await dispatch(STORE_NAME).updateRelatedEntities(9, 'basic', 'post-to-post', entities);

		expect(
			select(STORE_NAME).getRelatedEntities(9, {
				rel_key: 'basic',
				rel_type: 'post-to-post',
			}),
		).toEqual(entities);
		expect(select(STORE_NAME).getDirtyEntityIds()).toContain(9);
	});

	it('is a no-op for a null post ID', async () => {
		await dispatch(STORE_NAME).updateRelatedEntities(null, 'basic', 'post-to-post', []);

		expect(select(STORE_NAME).getDirtyEntityIds()).toEqual([]);
	});
});

describe('persistContentConnectChanges', () => {
	it('sends only valid numeric IDs for each dirty post relationship and clears dirty state', async () => {
		mockApi.updateRelatedEntities.mockResolvedValue([] as never);

		const postId = 5;
		dispatch(STORE_NAME).setRelationships('relationships-5-{}', {
			basic: { rel_key: 'basic', rel_type: 'post-to-post' },
		});
		dispatch(STORE_NAME).setRelatedEntities('related-5-basic', [
			{ id: '11', name: 'a', type: 'post', uuid: 'u1' }, // string ID -> parsed
			{ id: 12, name: 'b', type: 'post', uuid: 'u2' }, // numeric ID kept
			{ id: 'nope', name: 'c', type: 'post', uuid: 'u3' }, // NaN -> filtered
			{ id: 0, name: 'd', type: 'post', uuid: 'u4' }, // 0 -> filtered
		]);
		dispatch(STORE_NAME).markPostAsDirty(postId);

		await persistContentConnectChanges();

		expect(mockApi.updateRelatedEntities).toHaveBeenCalledTimes(1);
		expect(mockApi.updateRelatedEntities).toHaveBeenCalledWith(
			5,
			'basic',
			'post-to-post',
			[11, 12],
		);
		expect(select(STORE_NAME).getDirtyEntityIds()).toEqual([]);
	});

	it('does nothing when there are no dirty posts', async () => {
		await persistContentConnectChanges();

		expect(mockApi.updateRelatedEntities).not.toHaveBeenCalled();
	});
});

describe('editor.preSavePost filter', () => {
	it('registers a callback at module load', () => {
		expect(typeof preSaveCallback).toBe('function');
	});

	function seedDirtyPost() {
		dispatch(STORE_NAME).setRelationships('relationships-5-{}', {
			basic: { rel_key: 'basic', rel_type: 'post-to-post' },
		});
		dispatch(STORE_NAME).setRelatedEntities('related-5-basic', [
			{ id: 11, name: 'a', type: 'post', uuid: 'u1' },
		]);
		dispatch(STORE_NAME).markPostAsDirty(5);
	}

	it('persists changes and returns edits on a real save', async () => {
		mockApi.updateRelatedEntities.mockResolvedValue([] as never);
		seedDirtyPost();

		const edits = { title: 'x' };
		const result = await preSaveCallback(edits, { isAutosave: false, isPreview: false });

		expect(mockApi.updateRelatedEntities).toHaveBeenCalledWith(
			5,
			'basic',
			'post-to-post',
			[11],
		);
		expect(result).toBe(edits);
	});

	it('skips persistence on autosave', async () => {
		seedDirtyPost();

		const edits = { title: 'x' };
		const result = await preSaveCallback(edits, { isAutosave: true, isPreview: false });

		expect(mockApi.updateRelatedEntities).not.toHaveBeenCalled();
		expect(result).toBe(edits);
	});

	it('swallows persistence errors and still returns edits', async () => {
		mockApi.updateRelatedEntities.mockRejectedValue(new Error('boom'));
		seedDirtyPost();

		const edits = { title: 'x' };
		const result = await preSaveCallback(edits, { isAutosave: false, isPreview: false });

		expect(result).toBe(edits);
		expect(console.error).toHaveBeenCalled();
	});
});
