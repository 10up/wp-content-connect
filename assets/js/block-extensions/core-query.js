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
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { store } from '../store';

const BlockEdit = ({ setAttributes, attributes }) => {
	const {
		query: { postType: queriedPostType },
		showRelated,
		relationshipName,
		sourcePosts,
	} = attributes;

	const {
		postTypes,
		postTypeRelationships,
		hasPostRelationships,
		currentPostId,
		currentPostType,
	} = useSelect(
		(select) => {
			const { getPostTypes } = select(coreStore);
			const excludedPostTypes = ['attachment'];
			const filteredPostTypes = getPostTypes({ per_page: -1 })?.filter(
				({ viewable, slug }) => viewable && !excludedPostTypes.includes(slug),
			);

			const currentPostId = select(editorStore).getCurrentPostId();
			const currentPostType = select(editorStore).getCurrentPostType();
			const postRelationships = select(store).getRelationships(
				sourcePosts?.[0]?.id || currentPostId,
			);
			const postTypeRelationships = Object.values(postRelationships).filter(
				(relationship) =>
					Array.isArray(relationship.post_type) &&
					relationship.post_type.includes(queriedPostType),
			);

			return {
				postTypes: filteredPostTypes,
				postTypeRelationships,
				hasPostRelationships: postTypeRelationships.length > 0,
				currentPostId,
				currentPostType,
			};
		},
		[queriedPostType, sourcePosts?.length],
	);

	const postTypesSlugs = useMemo(() => (postTypes || []).map(({ slug }) => slug), [postTypes]);

	const postTypeRelationshipsOptions = useMemo(
		() =>
			postTypeRelationships.map((relationship) => ({
				value: relationship.rel_name,
				label: relationship.labels.name,
			})),
		[postTypeRelationships],
	);

	const postTypeRelationshipsControlLabel = __('Relationship', 'tenup-content-connect');
	const postTypeRelationshipsControlHelp = __(
		'Select a relationship to determine how related items are retrieved.',
		'tenup-content-connect',
	);
	const sourcePostsControlHelp = __(
		'Choose the post from which related items will be pulled. Defaults to the current post.',
		'tenup-content-connect',
	);

	const onPostTypeRelationshipChange = (value) => {
		setAttributes({ relationshipName: value });
	};

	const resetAll = () => {
		setAttributes({
			showRelated: false,
			sourcePosts: undefined,
			relationshipName: undefined,
		});
	};

	return (
		<InspectorControls>
			<PanelBody title={__('Related', 'tenup-content-connect')} initialOpen={false}>
				<ToggleControl
					label={__('Only show related items', 'tenup-content-connect')}
					checked={showRelated}
					onChange={(value) => {
						if (!value) {
							resetAll();
						} else {
							setAttributes({
								showRelated: value,
								sourcePosts: [
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
					<BaseControl help={sourcePostsControlHelp}>
						<ContentPicker
							onPickChange={(ids) =>
								setAttributes({ sourcePosts: ids.length ? ids : undefined })
							}
							mode="post"
							content={sourcePosts}
							contentTypes={postTypesSlugs}
							singlePickedLabel={__('Selected post:', 'tenup-content-connect')}
							multiPickedLabel={__('Selected posts:', 'tenup-content-connect')}
						/>
					</BaseControl>
				)}
				{showRelated && postTypeRelationshipsOptions.length > 1 && (
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						options={postTypeRelationshipsOptions}
						value={relationshipName}
						label={postTypeRelationshipsControlLabel}
						onChange={onPostTypeRelationshipChange}
						help={postTypeRelationshipsControlHelp}
					/>
				)}
				{showRelated && !hasPostRelationships && (
					<Notice spokenMessage={null} status="warning" isDismissible={false}>
						{__(
							'No relationships exist for the selected post type or post. Try selecting a different post or post type.',
							'tenup-content-connect',
						)}
					</Notice>
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
		sourcePosts: {
			type: 'array',
		},
		relationshipName: {
			type: 'string',
		},
	},
	classNameGenerator: () => '',
	Edit: BlockEdit,
});
