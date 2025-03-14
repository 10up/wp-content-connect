/* eslint-disable import/extensions */
/**
 * External dependencies
 */
import { registerBlockExtension, ContentPicker } from '@10up/block-components';

/**
 * WordPress dependencies
 */
import { ToggleControl, PanelBody, Notice } from '@wordpress/components';
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
		query: { postType: toPostType },
		showRelated,
		fromPosts,
	} = attributes;

	const { postTypes, currentPostId, hasPostRelationships } = useSelect(
		(select) => {
			const { getPostTypes } = select(coreStore);
			const excludedPostTypes = ['attachment'];
			const filteredPostTypes = getPostTypes({ per_page: -1 })?.filter(
				({ viewable, slug }) => viewable && !excludedPostTypes.includes(slug),
			);

			const postId = select(editorStore).getCurrentPostId();
			const postRelationships = select(store).getRelationships(fromPosts?.[0]?.id || postId);
			const postTypeRelationships = Object.values(postRelationships).filter(
				(relationship) =>
					Array.isArray(relationship.post_type) &&
					relationship.post_type.includes(toPostType),
			);

			return {
				postTypes: filteredPostTypes,
				currentPostId: postId,
				hasPostRelationships: postTypeRelationships.length > 0,
			};
		},
		[toPostType, fromPosts?.length],
	);

	const postTypeSlugs = useMemo(() => (postTypes || []).map(({ slug }) => slug), [postTypes]);

	return (
		<InspectorControls>
			<PanelBody title={__('Related', 'tenup-content-connect')} initialOpen={false}>
				<ToggleControl
					label={__('Only show related items', 'tenup-content-connect')}
					disabled={!hasPostRelationships}
					checked={showRelated}
					onChange={(value) =>
						setAttributes({
							showRelated: value,
							sourcePostId: currentPostId,
						})
					}
					help={__(
						'When enabled, only items related to the current post will be displayed.',
						'tenup-content-connect',
					)}
					__nextHasNoMarginBottom
				/>
				{!hasPostRelationships && (
					<Notice spokenMessage={null} status="warning" isDismissible={false}>
						{__(
							'No relationships exist for the selected post type. Try selecting a different post type.',
							'tenup-content-connect',
						)}
					</Notice>
				)}
				{showRelated && (
					<>
						<ContentPicker
							onPickChange={(ids) =>
								setAttributes({ fromPosts: ids.length ? ids : undefined })
							}
							mode="post"
							content={fromPosts}
							contentTypes={postTypeSlugs}
						/>
						<Notice spokenMessage={null} status="warning" isDismissible={false}>
							{__(
								'Select a different post as the source for related items. If none is selected, the current post will be used.',
								'tenup-content-connect',
							)}
						</Notice>
					</>
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
		fromPosts: {
			type: 'array',
			default: [],
		},
	},
	classNameGenerator: () => '',
	Edit: BlockEdit,
});
