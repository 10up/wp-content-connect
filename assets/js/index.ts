/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import './store';
import './hooks';
import { RelationshipsPanel } from './components/relationships-panel';

registerPlugin('wp-content-connect', {
	render: RelationshipsPanel,
});
