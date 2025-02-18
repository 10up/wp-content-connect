import { store } from '../store';
import { useSelect, useDispatch } from '@wordpress/data';
import { useCallback } from 'react';

export function useRelatedPosts(postId: number,
	options: {
		rel_key: string;
		order?: 'desc' | 'asc';
		orderby?: string;
		per_page?: number;
		page?: number;
	}) {
		const { hasResolved, relatedPosts } = useSelect(
			(select) => {
				const params = [postId, options] as const;
				const relatedPosts = select(store).getRelatedPosts(...params);
				// @ts-expect-error - The hasFinishedResolution method is a meta-method that coming
				// from WordPress. Because of that, it's not typed correctly in our custom store.
				const hasResolved: boolean = select(store).hasFinishedResolution('getRelatedPosts', params);

				return {
					relatedPosts,
					hasResolved,
			};
		},
		[postId, options]
	);

	const { updateRelatedPosts } = useDispatch(store);

	const _updateRelatedPosts = useCallback((relatedIds: number[]) => {
		updateRelatedPosts(postId, options.rel_key, relatedIds);
	}, [postId, options, updateRelatedPosts]);

	return [hasResolved, relatedPosts, _updateRelatedPosts] as const;
}
