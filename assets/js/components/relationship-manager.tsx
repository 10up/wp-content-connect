/* global contentConnect */
import React from 'react';
import { ContentPicker } from '@10up/block-components';
import { useSelect, useDispatch } from '@wordpress/data';
import { addQueryArgs } from '@wordpress/url';
import { decodeEntities } from '@wordpress/html-entities';
import { __experimentalText as Text  } from '@wordpress/components';
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
	const { title } = item;
	const decodedTitle = decodeEntities(title);

	const {
		pickedItem: {
			truncate = true,
			ellipsizeMode = 'auto',
			numberOfLines = 1
		}
	} = contentConnect;

	return (
		<Text truncate={truncate} ellipsizeMode={ellipsizeMode} numberOfLines={numberOfLines} title={decodedTitle} aria-label={decodedTitle}>{decodedTitle}</Text>
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
		await updateRelatedEntities(
			postId,
			relationship.rel_key,
			relationship.rel_type,
			newEntities
		);
	};

	return (
		<ContentPicker
			onPickChange={handleChange}
			mode={relationship?.object_type ?? 'post'}
			content={relatedEntities}
			contentTypes={relationship?.post_type}
			maxContentItems={relationship?.max_items ?? 100}
			isOrderable={relationship?.sortable ?? false}
			queryFilter={(query) => {
				if (relationship?.rel_key) {
					return addQueryArgs(query, {
						content_connect: relationship.rel_key
					});
				}
				return query;
			}}
			PickedItemPreviewComponent={PickedRelationshipPreview}
		/>
	);
}
