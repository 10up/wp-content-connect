import { store } from '../store';
import { useSelect } from '@wordpress/data';
import { GetRelationshipsOptions } from '../store/api';

export function useRelationships(
	postId: number,
	options?: GetRelationshipsOptions
) {
	const { relationships, hasResolved } = useSelect(
		(select) => {
			const params = [postId, options] as const;
			const relationships = select(store).getRelationships(...params);
			// @ts-expect-error - The hasFinishedResolution method is a meta-method that coming
			// from WordPress. Because of that, it's not typed correctly in our custom store.
			const hasResolved: boolean = select(store).hasFinishedResolution('getRelationships', params);

			return {
				relationships,
				hasResolved,
			};
		},
		[postId, options]
	);

	return [hasResolved, relationships] as const;
}
