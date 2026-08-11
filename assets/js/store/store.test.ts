/**
 * @jest-environment node
 */
import { createReduxStore, register, select, dispatch } from '@wordpress/data';

jest.mock('@wordpress/api-fetch');
jest.mock('@wordpress/hooks', () => ({ addFilter: jest.fn() }));
jest.mock('@wordpress/editor', () => ({ store: 'core/editor-mock' }));

const editPostSpy = jest.fn();

// Minimal fake editor store so markPostAsDirty's editor calls resolve.
const editorMock = createReduxStore('core/editor-mock', {
	reducer: (state = { meta: { existing_meta: 'keep' } }) => state,
	selectors: {
		getEditedPostAttribute: (state: { meta: Record<string, unknown> }) => state.meta,
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
import { STORE_NAME } from './index';

beforeEach(() => {
	editPostSpy.mockClear();
	dispatch(STORE_NAME).clearDirtyEntities();
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
