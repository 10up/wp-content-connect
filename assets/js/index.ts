import './store';
import './hooks';

import { registerPlugin } from '@wordpress/plugins';
import { RelationshipsPanel } from './components/relationships-panel';

registerPlugin('wp-content-connect', {
	render: RelationshipsPanel,
});
