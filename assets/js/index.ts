import './store';
import './hooks';
import './block-extensions';

import { registerPlugin } from '@wordpress/plugins';
import { RelationshipsPanel } from './components/relationships-panel';

registerPlugin('wp-content-connect', {
	render: RelationshipsPanel,
});
