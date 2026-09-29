/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Row } from '../types';
import { EditorTarget, useEditorState } from '../hooks/use-editor-target';
import { RelationshipEditor } from './RelationshipEditor';

type EditorPanelProps = {
	target: EditorTarget;
	rows: Row[];
	isLoading: boolean;
	onClose(): void;
};

/**
 * Opens the relationship editor for a target, once the target resolves.
 */
export function EditorPanel({ target, rows, isLoading, onClose }: EditorPanelProps) {
	const state = useEditorState(target, rows, isLoading);

	switch (state.status) {
		case 'loading':
			return (
				<div className="content-connect-editor__placeholder">
					<Spinner />
				</div>
			);
		case 'missing':
			return (
				<div className="content-connect-editor__placeholder">
					<p>{__('This relationship no longer exists.', 'wp-content-connect')}</p>
					<Button variant="secondary" onClick={onClose} __next40pxDefaultSize>
						{__('Close', 'wp-content-connect')}
					</Button>
				</div>
			);
		default:
			return (
				<RelationshipEditor
					key={state.key}
					postId={state.postId}
					initialValues={state.initialValues}
					onClose={onClose}
				/>
			);
	}
}
