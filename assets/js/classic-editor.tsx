/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { RelationshipManager } from './components/relationship-manager';
import { ContentConnectRelationship } from './store/types';

/**
 * Register the relationships panels.
 */
const registerPanels = () => {
	const containers = document.querySelectorAll<HTMLElement>('[data-content-connect]');
	if (!containers.length) {
		return;
	}

	containers.forEach((container) => {
		const { postId, relationship: relationshipJson } = container.dataset;

		let relationshipData: ContentConnectRelationship | false = false;
		try {
			relationshipData = JSON.parse(relationshipJson || '') as ContentConnectRelationship;
		} catch (e) {
			console.error('Invalid JSON in data-content-connect:', e);
			return;
		}

		if (container && relationshipData) {
			const root = createRoot(container);
			root.render(
				<RelationshipManager
					key={relationshipData.rel_key}
					postId={parseInt(postId ?? '0', 10)}
					relationship={relationshipData}
				/>
			);
		}
	});
}

domReady(registerPanels);
