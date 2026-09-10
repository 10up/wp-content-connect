/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import './store';
import './hooks';
import './block-extensions';

import { RelationshipsPanel } from './components/RelationshipsPanel';

registerPlugin('wp-content-connect', {
	render: RelationshipsPanel,
});
