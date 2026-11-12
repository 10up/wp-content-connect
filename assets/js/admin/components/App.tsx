/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { SnackbarList } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { EditorTarget } from '../hooks/use-editor-target';
import { usePostTypeElements } from '../hooks/use-post-type-elements';
import { useRelationshipRows } from '../hooks/use-relationship-rows';
import { RelationshipsList } from './RelationshipsList';
import { EditorPanel } from './EditorPanel';
import { PageHeader } from './PageHeader';
import { SlideOut } from './SlideOut';

/**
 * The relationships screen for WordPress versions without the boot layout,
 * with the editor in a slide-out panel.
 */
export function App() {
	const [target, setTarget] = useState<EditorTarget | null>(null);
	const { isLoading, rows } = useRelationshipRows();
	const [, postTypes] = usePostTypeElements();

	const notices = useSelect((select) => select(noticesStore).getNotices(), []);
	const snackbars = useMemo(
		() => notices.filter((notice) => notice.type === 'snackbar'),
		[notices],
	);
	const { removeNotice } = useDispatch(noticesStore);

	const onClose = () => setTarget(null);

	return (
		<div className="content-connect-admin">
			<PageHeader onAdd={() => setTarget({ type: 'new' })} />

			<div className="content-connect-admin__list">
				<RelationshipsList
					rows={rows}
					isLoading={isLoading}
					postTypes={postTypes}
					onEdit={(row) => setTarget({ type: 'edit', postId: row.postId as number })}
					onDuplicate={(row) => setTarget({ type: 'duplicate', rowId: row.id })}
				/>
			</div>

			{target && (
				<SlideOut
					label={
						target.type === 'edit'
							? __('Edit relationship', 'wp-content-connect')
							: __('Add relationship', 'wp-content-connect')
					}
					onClose={onClose}
				>
					<EditorPanel
						target={target}
						rows={rows}
						isLoading={isLoading}
						onClose={onClose}
					/>
				</SlideOut>
			)}

			<SnackbarList
				className="content-connect-admin__snackbars"
				notices={snackbars}
				onRemove={removeNotice}
			/>
		</div>
	);
}
