/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { useNavigate, useSearch } from '@wordpress/route';

/**
 * Internal dependencies
 */
import { RelationshipsList } from '../../admin/components/RelationshipsList';
import { EditorPanel } from '../../admin/components/EditorPanel';
import { PageHeader } from '../../admin/components/PageHeader';
import { EditorTarget } from '../../admin/hooks/use-editor-target';
import { usePostTypeElements } from '../../admin/hooks/use-post-type-elements';
import { useRelationshipRows } from '../../admin/hooks/use-relationship-rows';
import '../../../css/admin-relationships.css';

type EditorSearch = {
	edit?: number | string;
	duplicate?: string;
};

// Boot owns the router, so its routes are unknown to the type system here.
interface NavigateToSearch {
	(options: { search: EditorSearch }): Promise<void>;
}

const useSearchNavigate = () => useNavigate() as unknown as NavigateToSearch;

/**
 * Reads the editor target from the route search: `edit=new`, `edit=<id>` or
 * `duplicate=<row id>`.
 */
const getTarget = (search: EditorSearch): EditorTarget | null => {
	if (search.duplicate) {
		return { type: 'duplicate', rowId: String(search.duplicate) };
	}

	if (search.edit === 'new') {
		return { type: 'new' };
	}

	const postId = Number(search.edit);

	return postId > 0 ? { type: 'edit', postId } : null;
};

function Stage() {
	const navigate = useSearchNavigate();
	const { isLoading, rows } = useRelationshipRows();
	const [, postTypes] = usePostTypeElements();

	const open = (search: EditorSearch) => navigate({ search });

	return (
		<div className="content-connect-admin content-connect-admin--boot">
			<PageHeader onAdd={() => open({ edit: 'new' })} />

			<div className="content-connect-admin__stage-content">
				<RelationshipsList
					rows={rows}
					isLoading={isLoading}
					postTypes={postTypes}
					onEdit={(row) => open({ edit: row.postId })}
					onDuplicate={(row) => open({ duplicate: row.id })}
				/>
			</div>
		</div>
	);
}

function Inspector() {
	const navigate = useSearchNavigate();
	const search = useSearch({ strict: false }) as EditorSearch;
	const { isLoading, rows } = useRelationshipRows();

	const target = getTarget(search);

	if (!target) {
		return null;
	}

	return (
		<div className="content-connect-admin-inspector">
			<EditorPanel
				target={target}
				rows={rows}
				isLoading={isLoading}
				onClose={() => navigate({ search: {} })}
			/>
		</div>
	);
}

export const stage = Stage;
export const inspector = Inspector;
