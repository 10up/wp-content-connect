/**
 * WordPress dependencies
 */
import { useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { FormValues, Row } from '../types';
import { emptyFormValues, rowToFormValues } from '../utils/records';

// What the editor panel is open for.
export type EditorTarget =
	{ type: 'new' } | { type: 'edit'; postId: number } | { type: 'duplicate'; rowId: string };

export type EditorState =
	| { status: 'loading' }
	| { status: 'missing' }
	| { status: 'ready'; key: string; postId?: number; initialValues: FormValues };

/**
 * Resolves an editor target against the relationship rows into the values the
 * editor opens with.
 */
export const resolveEditorState = (
	target: EditorTarget,
	rows: Row[],
	isLoading: boolean,
): EditorState => {
	if (target.type === 'new') {
		return { status: 'ready', key: 'new', initialValues: emptyFormValues() };
	}

	if (isLoading) {
		return { status: 'loading' };
	}

	if (target.type === 'edit') {
		const row = rows.find((item) => item.postId === target.postId);

		return row
			? {
					status: 'ready',
					key: `edit-${target.postId}`,
					postId: target.postId,
					initialValues: rowToFormValues(row),
				}
			: { status: 'missing' };
	}

	const row = rows.find((item) => item.id === target.rowId);

	return row
		? {
				status: 'ready',
				key: `duplicate-${target.rowId}`,
				initialValues: {
					...rowToFormValues(row),
					/* translators: %s: relationship label */
					title: sprintf(__('%s (copy)', 'wp-content-connect'), row.label),
					rel_name: '',
				},
			}
		: { status: 'missing' };
};

/**
 * Returns the editor state for a target.
 *
 * The editor reads `initialValues` once on mount and is keyed by `key`, so a
 * later refetch of the rows does not reset what is being edited.
 */
export function useEditorState(target: EditorTarget, rows: Row[], isLoading: boolean): EditorState {
	return useMemo(() => resolveEditorState(target, rows, isLoading), [target, rows, isLoading]);
}
