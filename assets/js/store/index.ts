import apiFetch from '@wordpress/api-fetch';
import { createReduxStore, register, select, dispatch } from '@wordpress/data';
import { addQueryArgs } from '@wordpress/url';
import { addFilter } from '@wordpress/hooks';

/**
 * Store defaults
 */
const DEFAULT_STATE = {
	relationships: {},
};

export const store = createReduxStore('wp-content-connect', {
	reducer(state = DEFAULT_STATE, action = '') {
		switch (action.type) {
			default:
				break;
		}

		return state;
	},
	actions: {},
	selectors: {},
	controls: {},
	resolvers: {},
});

register(store);

async function persistContentConnectionChanges() {
	// TODO: Implement
}

addFilter(
	'editor.preSavePost',
	'wp-content-connect/persist-connections',
	async ( edits, options: { readonly isAutosave: boolean, readonly isPreview: boolean } ) => {
		try {
			await persistContentConnectionChanges();
			return edits;
		} catch (error) {
			console.error(error);
			return edits;
		}
	}
);
