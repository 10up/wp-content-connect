import React from 'react';
import { FormTokenField } from '@wordpress/components';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useSelect, useDispatch, select } from '@wordpress/data';
import { useEffect, useState, useCallback } from '@wordpress/element';
import { store as coreStore, Post } from '@wordpress/core-data';
import { store } from '../store';
import { ContentConnectRelationship } from '../store/types';
import { decodeEntities } from '@wordpress/html-entities';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

type RelationshipManagerProps = {
	postId: number | null;
	relationship: ContentConnectRelationship;
};

export function RelationshipManager({ postId, relationship }: RelationshipManagerProps) {
	const { updateRelatedPosts } = useDispatch(store);
	const [suggestions, setSuggestions] = useState<string[]>([]);
	const [searchTerm, setSearchTerm] = useState('');
	const [currentSearch, setCurrentSearch] = useState('');

	const { relatedPosts, searchResults } = useSelect((select) => ({
		relatedPosts: select(store).getRelatedPosts(postId, {
			rel_key: relationship.rel_key,
		}),
		searchResults: searchTerm ? select(coreStore).getEntityRecords<Post>(
			'postType',
			relationship.post_type[0],
			{
				search: searchTerm,
				per_page: 20,
				orderby: 'title',
				order: 'asc',
			}
		) : [],
	}), [postId, relationship.rel_key, relationship.post_type, searchTerm]);

	async function getPostByTitle(title: string) {
		const result = await apiFetch<{ id: number }[]>({
			path: addQueryArgs(`/wp/v2/${relationship.post_type[0]}`, {
				search: title,
				per_page: 1,
				_fields: 'id',
			}),
		});
		return result[0]?.id ?? undefined;
	}

	// Convert related posts to token format
	const tokens = relatedPosts.map((post) => post.name);

	// Update suggestions based on search
	useEffect(() => {
		if (!searchResults) {
			setSuggestions([]);
			return;
		}

		const newSuggestions = searchResults
			.filter((post) => !relatedPosts.find((related) => related.ID === post.id))
			.map((post) => decodeEntities(post.title.rendered));

		setSuggestions(newSuggestions);
	}, [searchResults, relatedPosts]);

	const handleChange = async (newTokens: any[]) => {
		const newRelatedIds = await Promise.all(
			newTokens.map(async (token) => {
				const tokenValue = typeof token === 'string' ? token : token.value;
				return getPostByTitle(tokenValue);
			})
		);

		updateRelatedPosts(postId, relationship.rel_key, newRelatedIds.filter((id): id is number => id !== undefined));
	};

	return (
		<PluginDocumentSettingPanel
			name={`content-connect-relationship-${relationship.rel_key}`}
			title={relationship.labels.name}
		>
			<FormTokenField
				value={tokens}
				suggestions={suggestions}
				onChange={handleChange}
				onInputChange={(input) => setSearchTerm(input)}
				label={relationship.labels.name}
				__next40pxDefaultSize={true}
				__experimentalShowHowTo={false}
			/>
		</PluginDocumentSettingPanel>
	);
}
