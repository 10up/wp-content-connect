import { store } from '../store';
import { useSelect, useDispatch } from '@wordpress/data';
import { useCallback } from 'react';
import { GetRelatedEntitiesOptions } from '../store/api';
import { ContentConnectRelatedEntities } from '../store/types';

export function useRelatedEntities(postId: number, options: GetRelatedEntitiesOptions) {
		const { hasResolved, relatedEntities } = useSelect(
			(select) => {
				const params = [postId, options] as const;
				const relatedEntities = select(store).getRelatedEntities(...params);
				// @ts-expect-error - The hasFinishedResolution method is a meta-method that coming
				// from WordPress. Because of that, it's not typed correctly in our custom store.
				const hasResolved: boolean = select(store).hasFinishedResolution('getRelatedEntities', params);

				return {
					relatedEntities,
					hasResolved,
			};
		},
		[postId, options]
	);

	const { updateRelatedEntities } = useDispatch(store);

	const _updateRelatedEntities = useCallback((entities: ContentConnectRelatedEntities) => {
		updateRelatedEntities(postId, options.rel_key, options.rel_type, entities);
	}, [postId, options, updateRelatedEntities]);

	return [hasResolved, relatedEntities, _updateRelatedEntities] as const;
}
