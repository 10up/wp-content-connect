/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockVariation } from '@wordpress/blocks';

const VARIATION_NAME = 'content-connect/related-posts';

registerBlockVariation('core/query', {
	name: VARIATION_NAME,
	title: __('Related Posts', 'content-connect'),
	description: __('Displays a list of related posts.', 'content-connect'),
	isActive: ({ namespace, query }) => {
		return namespace === VARIATION_NAME;
	},
	attributes: {
		namespace: VARIATION_NAME,
	},
	allowedControls: [
		'inherit',
		'postType',
		'order',
		'sticky',
		'taxQuery',
		'author',
		'search',
		'format',
		'parents'
	],
	scope: ['inserter'],
});
