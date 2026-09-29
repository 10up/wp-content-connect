/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

type PageHeaderProps = {
	onAdd(): void;
};

export function PageHeader({ onAdd }: PageHeaderProps) {
	return (
		<div className="content-connect-admin__header">
			<div className="content-connect-admin__heading">
				<h1 className="content-connect-admin__title">
					{__('Content Connect', 'wp-content-connect')}
				</h1>
				<Button variant="secondary" onClick={onAdd} __next40pxDefaultSize>
					{__('Add new Relationship', 'wp-content-connect')}
				</Button>
			</div>
			<p className="content-connect-admin__description">
				{__(
					'Relationships connect posts to other posts or to users, and add a panel to the editor for managing those connections. Relationships registered from code are listed read-only.',
					'wp-content-connect',
				)}
			</p>
		</div>
	);
}
