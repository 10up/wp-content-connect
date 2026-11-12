/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { App } from './components/App';

domReady(() => {
	const container = document.getElementById('content-connect-admin');

	if (!container) {
		return;
	}

	createRoot(container).render(<App />);
});
