import React from 'react';
import { ContentPicker } from '@10up/block-components';
import { useSelect, useDispatch } from '@wordpress/data';
import { safeDecodeURI } from '@wordpress/url';
import { decodeEntities } from '@wordpress/html-entities';
import { __experimentalTruncate as Truncate } from '@wordpress/components';
import { store } from '../store';
import { ContentConnectRelationship } from '../store/types';

type RelationshipManagerProps = {
	postId: number | null;
	relationship: ContentConnectRelationship;
};

type PickedRelationshipType = {
	id: number;
	type: string;
	uuid: string;
	title: string;
	url: string;
};

/**
 * Component to render a preview of a picked relationship.
 *
 * @component
 * @param {object} props - The component props.
 * @param {PickedRelationshipType} props.item - The picked relationship to display.
 * @returns {*} React JSX
 */
const PickedRelationshipPreview: React.FC<{ item: PickedRelationshipType }> = ({ item }) => {
	const { title, type, url = '' } = item;
	return (
		<>
			{type !== 'user' ? (
				<a href={safeDecodeURI(url) || ''} target="_blank" rel="noopener noreferrer">
					<Truncate>{decodeEntities(title)}</Truncate>
				</a>
			) : (
				<Truncate>{decodeEntities(title)}</Truncate>
			)}
		</>
	);
};

export function RelationshipManager({ postId, relationship }: RelationshipManagerProps) {
	const { updateRelatedEntities } = useDispatch(store);

	const { relatedEntities } = useSelect((select) => ({
		relatedEntities: select(store).getRelatedEntities(postId, {
			rel_key: relationship.rel_key,
			rel_type: relationship.rel_type,
		}),
	}), [postId, relationship.rel_key]);

	const handleChange = async (newEntities: any[]) => {
		const newIds = newEntities.map(entity => entity.id);
		updateRelatedEntities(postId, relationship.rel_key, relationship.rel_type, newIds);
	};

	return (
		<ContentPicker
			onPickChange={handleChange}
			mode={relationship?.object_type ?? 'post'}
			content={relatedEntities}
			contentTypes={relationship?.post_type}
			maxContentItems={relationship?.max_items ?? 100}
			isOrderable={relationship?.sortable ?? false}
			PickedItemPreviewComponent={PickedRelationshipPreview}
		/>
	);
}
