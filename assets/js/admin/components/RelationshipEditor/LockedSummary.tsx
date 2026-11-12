/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { FormValues, PostTypeElement } from '../../types';

type LockedSummaryProps = {
	values: FormValues;
	postTypes: PostTypeElement[];
};

/**
 * Shows the fields that identify stored connections, which cannot change once
 * the relationship is saved.
 */
export function LockedSummary({ values, postTypes }: LockedSummaryProps) {
	const toLabel = (slug: string) =>
		postTypes.find((postType) => postType.value === slug)?.label ?? slug;

	const isPostToPost = values.rel_type === 'post-to-post';

	return (
		<Notice status="info" isDismissible={false} className="content-connect-admin__locked">
			<p>
				{__(
					'Relationships depend on these settings, so they cannot change.',
					'wp-content-connect',
				)}
			</p>
			<dl className="content-connect-admin__locked-list">
				<dt>{__('Type', 'wp-content-connect')}</dt>
				<dd>
					{isPostToPost
						? __('Posts to posts', 'wp-content-connect')
						: __('Posts to users', 'wp-content-connect')}
				</dd>
				<dt>{__('Name', 'wp-content-connect')}</dt>
				<dd>
					<code>{values.rel_name}</code>
				</dd>
				<dt>{__('Post type', 'wp-content-connect')}</dt>
				<dd>{toLabel(values.rel_from)}</dd>
				{isPostToPost && (
					<>
						<dt>{__('Related post types', 'wp-content-connect')}</dt>
						<dd>{values.rel_to.map(toLabel).join(', ')}</dd>
					</>
				)}
			</dl>
		</Notice>
	);
}
