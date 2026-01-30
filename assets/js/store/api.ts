import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { ContentConnectRelatedEntities, ContentConnectRelationships, ContentConnectUpdateRelationshipsBody } from './types';

export const CONTENT_CONNECT_ENDPOINT = '/content-connect/v2';

export type GetRelationshipsOptions = {
	rel_type?: string;
	post_type?: string;
	context?: 'embed';
}

export async function getRelationships(
	postId: number,
	options?: GetRelationshipsOptions
) {
	try {
		const path = addQueryArgs(`${CONTENT_CONNECT_ENDPOINT}/post/${postId}/relationships`, options);
		const relationships = await apiFetch<ContentConnectRelationships>({ path });
		return relationships;
	} catch (error) {
		console.error('Failed to fetch relationships:', error);
		throw error;
	}
}

export type GetRelatedEntitiesOptions = {
	rel_key: string;
	rel_type: string;
	order?: 'desc' | 'asc';
	orderby?: string;
	per_page?: number;
	page?: number;
}

export async function getRelatedEntities(
	postId: number,
	options: GetRelatedEntitiesOptions
) {
	try {
		const path = addQueryArgs(`${CONTENT_CONNECT_ENDPOINT}/post/${postId}/related`, options);
		const relatedEntities = await apiFetch<ContentConnectRelatedEntities>({ path });
		return relatedEntities;
	} catch (error) {
		console.error('Failed to fetch related entities:', error);
		throw error;
	}
}

export async function updateRelatedEntities(
	postId: number,
	relKey: string,
	relType: string,
	relatedIds: number[]
) {
	try {
		const body: ContentConnectUpdateRelationshipsBody = {
			related_ids: relatedIds,
		};
		const path = addQueryArgs(`${CONTENT_CONNECT_ENDPOINT}/post/${postId}/related`, { rel_key: relKey, rel_type: relType });
		const relatedEntities = await apiFetch<ContentConnectRelatedEntities>({ path, method: 'POST', data: body });
		return relatedEntities;
	} catch (error) {
		console.error('Failed to update related entities:', error);
		throw error;
	}
}
