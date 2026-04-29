import { store } from '../store';
import { useSelect, useDispatch } from '@wordpress/data';
import { useCallback, useMemo } from 'react';
import { GetRelatedEntitiesOptions } from '../store/api';
import { ContentConnectRelatedEntities } from '../store/types';

export function useRelatedEntities(postId: number, options: GetRelatedEntitiesOptions) {
	// Serialize options to avoid unnecessary re-renders when parent re-renders
	const optionsKey = useMemo(
		() => JSON.stringify(options),
		[options.rel_key, options.rel_type, options.order, options.orderby, options.per_page, options.page]
	);

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
		[postId, optionsKey]
	);

	const { updateRelatedEntities } = useDispatch(store);

	const _updateRelatedEntities = useCallback((entities: ContentConnectRelatedEntities) => {
		updateRelatedEntities(postId, options.rel_key, options.rel_type, entities);
	}, [postId, options.rel_key, options.rel_type, updateRelatedEntities]);

	return [hasResolved, relatedEntities, _updateRelatedEntities] as const;
}
