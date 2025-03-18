/* eslint-disable import/no-extraneous-dependencies */
/* eslint-disable import/extensions */
/**
 * External dependencies
 */
import { v4 as uuidv4 } from 'uuid';
import { registerBlockExtension, ContentPicker } from '@10up/block-components';

/**
 * WordPress dependencies
 */
import {
	ToggleControl,
	PanelBody,
	Notice,
	SelectControl,
	BaseControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { store as coreStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { useMemo, useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { store } from '../store';

const BlockEdit = ({ setAttributes, attributes }) => {
	const { query, showRelated, relationshipKey, sourcePost, orderByRelationship } = attributes;
	const { postType: queriedPostType } = query;

	const { postTypes, relationships, hasRelationships, currentPostId, currentPostType } =
		useSelect(
			(select) => {
				const { getPostTypes } = select(coreStore);
				const excludedPostTypes = ['attachment'];
				const filteredPostTypes = getPostTypes({ per_page: -1 })?.filter(
					({ viewable, slug }) => viewable && !excludedPostTypes.includes(slug),
				);

				const currentPostId = select(editorStore).getCurrentPostId();
				const currentPostType = select(editorStore).getCurrentPostType();

				const postRelationships = select(store).getRelationships(
					sourcePost?.[0]?.id || currentPostId,
				);
				const filteredRelationships = Object.values(postRelationships).filter(
					(relationship) =>
						Array.isArray(relationship.post_type) &&
						relationship.post_type.includes(queriedPostType),
				);

				return {
					postTypes: filteredPostTypes,
					relationships: filteredRelationships,
					hasRelationships: filteredRelationships.length > 0,
					currentPostId,
					currentPostType,
				};
			},
			[queriedPostType, sourcePost?.length],
		);

	const postTypesSlugs = useMemo(() => (postTypes || []).map(({ slug }) => slug), [postTypes]);

	const relationshipsOptions = useMemo(
		() =>
			relationships.map((relationship) => ({
				value: relationship.rel_key,
				label: relationship.labels.name,
			})),
		[relationships],
	);

	const selectedRelationshipKey = useMemo(
		() =>
			relationshipKey && relationships.some((rel) => rel.rel_key === relationshipKey)
				? relationshipKey
				: relationships[0]?.rel_key,
		[relationshipKey, relationships],
	);

	const selectedRelationship = useMemo(
		() =>
			relationships.find((relationship) => relationship.rel_key === selectedRelationshipKey),
		[relationships, selectedRelationshipKey],
	);

	const lastQueryRef = useRef(query);

	const updatedQuery = useMemo(() => {
		if (!showRelated || !selectedRelationship) {
			const { relationshipQuery, orderByRelationship, ...cleanQuery } = query;
			return cleanQuery;
		}

		return {
			...query,
			orderByRelationship: orderByRelationship && selectedRelationship.sortable,
			relationshipQuery: [
				{
					name: selectedRelationship.rel_name,
					related_to_post: sourcePost?.[0]?.id || currentPostId,
				},
			],
		};
	}, [query, showRelated, orderByRelationship, selectedRelationship, sourcePost, currentPostId]);

	useEffect(() => {
		if (JSON.stringify(lastQueryRef.current) !== JSON.stringify(updatedQuery)) {
			setAttributes({ query: updatedQuery });
			lastQueryRef.current = updatedQuery;
		}
	}, [updatedQuery, setAttributes]);

	const relationshipsControlLabel = __('Relationship', 'tenup-content-connect');
	const relationshipsControlHelp = __(
		'Select a relationship to determine how related items are retrieved.',
		'tenup-content-connect',
	);
	const sourcePostControlHelp = __(
		'Choose the post from which related items will be pulled. Defaults to the current post.',
		'tenup-content-connect',
	);

	const onRelationshipChange = (value) => {
		setAttributes({ relationshipKey: value });
	};

	const resetAll = () => {
		setAttributes({
			showRelated: false,
			sourcePost: undefined,
			relationshipKey: undefined,
			orderByRelationship: true,
		});
	};

	return (
		<InspectorControls>
			<PanelBody title={__('Related', 'tenup-content-connect')} initialOpen={!!showRelated}>
				<ToggleControl
					label={__('Only show related items', 'tenup-content-connect')}
					checked={showRelated}
					onChange={(value) => {
						if (!value) {
							resetAll();
						} else {
							setAttributes({
								showRelated: value,
								sourcePost: [
									{
										id: currentPostId,
										type: currentPostType,
										uuid: uuidv4(),
									},
								],
							});
						}
					}}
				/>
				{showRelated && (
					<BaseControl help={sourcePostControlHelp}>
						<ContentPicker
							onPickChange={(ids) =>
								setAttributes({ sourcePost: ids.length ? ids : undefined })
							}
							mode="post"
							content={sourcePost}
							contentTypes={postTypesSlugs}
							singlePickedLabel={__('Selected post:', 'tenup-content-connect')}
							multiPickedLabel={__('Selected posts:', 'tenup-content-connect')}
						/>
					</BaseControl>
				)}
				{showRelated && relationshipsOptions.length > 1 && (
					<SelectControl
						options={relationshipsOptions}
						value={relationshipKey}
						label={relationshipsControlLabel}
						onChange={onRelationshipChange}
						help={relationshipsControlHelp}
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				)}
				{showRelated && !hasRelationships && (
					<Notice spokenMessage={null} status="warning" isDismissible={false}>
						{__(
							'No relationships exist for the selected post type or post. Try selecting a different post or post type.',
							'tenup-content-connect',
						)}
					</Notice>
				)}
				{showRelated && selectedRelationship?.sortable && (
					<ToggleControl
						label={__('Order by relationship', 'tenup-content-connect')}
						checked={orderByRelationship}
						onChange={(value) => setAttributes({ orderByRelationship: value })}
						help={__(
							'If enabled, the order of the related items will be determined by the relationship.',
							'tenup-content-connect',
						)}
					/>
				)}
			</PanelBody>
		</InspectorControls>
	);
};

registerBlockExtension('core/query', {
	extensionName: 'content-connect',
	attributes: {
		showRelated: {
			type: 'boolean',
			default: false,
		},
		sourcePost: {
			type: 'array',
		},
		relationshipKey: {
			type: 'string',
		},
		orderByRelationship: {
			type: 'boolean',
			default: true,
		},
	},
	classNameGenerator: () => '',
	Edit: BlockEdit,
});
