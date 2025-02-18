import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { ContentConnectRelatedPosts, ContentConnectRelationships, ContentConnectUpdateRelationshipsBody } from './types';

export const CONTENT_CONNECT_ENDPOINT = '/content-connect/v2';

export async function getRelationships(
	postId: number,
	options?: {
		rel_type?: string;
		post_type?: string;
		context?: 'embed';
	}
) {
	const path = addQueryArgs(`${CONTENT_CONNECT_ENDPOINT}/post/${postId}/relationships`, options);
	const relationships = await apiFetch<ContentConnectRelationships>({ path });
	return relationships;
}

export async function getRelatedPosts(
	postId: number,
	options: {
		rel_key: string;
		order?: 'desc' | 'asc';
		orderby?: string;
		per_page?: number;
		page?: number;
	}
) {
	const path = addQueryArgs(`${CONTENT_CONNECT_ENDPOINT}/post/${postId}/related`, options);
	const relatedPosts = await apiFetch<ContentConnectRelatedPosts>({ path });
	return relatedPosts;
}

export async function updateRelatedPosts(
	postId: number,
	relKey: string,
	relatedIds: number[]
) {
	const body: ContentConnectUpdateRelationshipsBody = {
		related_ids: relatedIds,
	};
	const path = addQueryArgs(`${CONTENT_CONNECT_ENDPOINT}/post/${postId}/related`, { rel_key: relKey });
	const relatedPosts = await apiFetch<ContentConnectRelatedPosts>({ path, method: 'POST', body: JSON.stringify(body) });
	return relatedPosts;
}
