/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useEntityRecords } from '@wordpress/core-data';
import { useEffect, useMemo, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CodeRelationship, CustomRelationshipRecord, Row } from '../types';
import { codeToRow, recordToRow } from '../utils/records';
import { RELATIONSHIP_POST_TYPE } from './use-post-type-elements';

const CUSTOM_RELATIONSHIPS_QUERY = {
	status: 'publish,draft',
	per_page: 100,
	context: 'edit',
};

const requestCodeRelationships = async (): Promise<CodeRelationship[]> => {
	const responses = await Promise.all(
		['post-to-post', 'post-to-user'].map((relType) =>
			apiFetch<Record<string, CodeRelationship>>({
				path: `/content-connect/v2/relationships?rel_type=${relType}`,
			}),
		),
	);

	return responses
		.flatMap((response) => Object.values(response ?? {}))
		.filter((relationship) => relationship.source === 'code');
};

let codeRelationshipsRequest: Promise<CodeRelationship[]> | null = null;

/**
 * Fetches the relationships registered from code for both relationship types.
 *
 * They only change on a page load, so the request is shared by every caller.
 */
const fetchCodeRelationships = (): Promise<CodeRelationship[]> => {
	codeRelationshipsRequest ??= requestCodeRelationships();

	return codeRelationshipsRequest;
};

/**
 * Returns every relationship as a list row: custom relationships first, then
 * the ones registered from code.
 */
export function useRelationshipRows(): { isLoading: boolean; rows: Row[] } {
	const { records, hasResolved } = useEntityRecords<CustomRelationshipRecord>(
		'postType',
		RELATIONSHIP_POST_TYPE,
		CUSTOM_RELATIONSHIPS_QUERY,
	);

	const [codeRelationships, setCodeRelationships] = useState<CodeRelationship[] | null>(null);

	useEffect(() => {
		fetchCodeRelationships()
			.then(setCodeRelationships)
			.catch(() => setCodeRelationships([]));
	}, []);

	const rows = useMemo(
		() => [...(records ?? []).map(recordToRow), ...(codeRelationships ?? []).map(codeToRow)],
		[records, codeRelationships],
	);

	return {
		isLoading: !hasResolved || codeRelationships === null,
		rows,
	};
}
