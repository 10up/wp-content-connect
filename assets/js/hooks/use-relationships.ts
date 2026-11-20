import { store } from '../store';
import { useSelect } from '@wordpress/data';
import { useMemo } from 'react';
import { GetRelationshipsOptions } from '../store/api';

export function useRelationships(
	postId: number,
	options?: GetRelationshipsOptions
) {
	// Serialize options to avoid unnecessary re-renders when parent re-renders
	const optionsKey = useMemo(() => JSON.stringify(options), [options?.rel_type, options?.post_type, options?.context]);

	const { relationships, hasResolved } = useSelect(
		(select) => {
			const params = [postId, options] as const;
			const relationships = select(store).getRelationships(...params);
			// @ts-expect-error - The hasFinishedResolution method is a meta-method that comes
			// from WordPress. Because of that, it's not typed correctly in our custom store.
			const hasResolved: boolean = select(store).hasFinishedResolution('getRelationships', params);

			return {
				relationships,
				hasResolved,
			};
		},
		[postId, optionsKey]
	);

	return [hasResolved, relationships] as const;
}
