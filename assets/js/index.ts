import './store';
import './hooks';

import { registerPlugin } from '@wordpress/plugins';
import { RelationshipsPanel } from './components/RelationshipsPanel';

registerPlugin('wp-content-connect', {
	render: RelationshipsPanel,
});
