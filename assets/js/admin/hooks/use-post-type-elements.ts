/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { PostTypeElement } from '../types';

export const RELATIONSHIP_POST_TYPE = 'cc_relationship';

// Post types that make no sense as a relationship end.
const EXCLUDED_POST_TYPES = [RELATIONSHIP_POST_TYPE, 'attachment', 'nav_menu_item'];

const QUERY = { per_page: -1 };

type PostType = {
	slug: string;
	name: string;
	visibility?: { show_ui?: boolean };
};

/**
 * Returns the post types a relationship can connect, as DataViews elements.
 */
export function usePostTypeElements(): [boolean, PostTypeElement[]] {
	const { postTypes, hasResolved } = useSelect(
		(select) => ({
			postTypes: select(coreStore).getPostTypes(QUERY) as PostType[] | null,
			hasResolved: select(coreStore).hasFinishedResolution('getPostTypes', [QUERY]),
		}),
		[],
	);

	const elements = useMemo(
		() =>
			(postTypes ?? [])
				.filter(
					(postType) =>
						!EXCLUDED_POST_TYPES.includes(postType.slug) &&
						!postType.slug.startsWith('wp_') &&
						postType.visibility?.show_ui !== false,
				)
				.map((postType) => ({ value: postType.slug, label: postType.name })),
		[postTypes],
	);

	return [hasResolved, elements];
}
