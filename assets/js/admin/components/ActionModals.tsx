/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { Button, Flex, TextareaControl } from '@wordpress/components';
import { useCopyToClipboard, useInstanceId } from '@wordpress/compose';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import type { RenderModalProps } from '@wordpress/dataviews/wp';
import { useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { Row } from '../types';
import { rowToPhp } from '../utils/to-php';
import { RELATIONSHIP_POST_TYPE } from '../hooks/use-post-type-elements';

export function CopyAsPhpModal({ items, closeModal }: RenderModalProps<Row>) {
	const code = rowToPhp(items[0]);
	// DataViews bundles its own components, whose generated IDs repeat ours.
	const id = useInstanceId(CopyAsPhpModal, 'content-connect-php');
	const { createSuccessNotice } = useDispatch(noticesStore);

	const copyRef = useCopyToClipboard(code, () => {
		createSuccessNotice(__('PHP copied to the clipboard.', 'wp-content-connect'), {
			type: 'snackbar',
		});
		closeModal?.();
	});

	return (
		<Flex direction="column" gap={4}>
			<TextareaControl
				id={id}
				className="content-connect-admin__code"
				label={__(
					'Add this to a plugin or theme to register the relationship from code.',
					'wp-content-connect',
				)}
				value={code}
				rows={Math.min(24, code.split('\n').length)}
				readOnly
				onChange={() => {}}
				__nextHasNoMarginBottom
			/>
			<Flex justify="flex-end">
				<Button variant="tertiary" onClick={closeModal} __next40pxDefaultSize>
					{__('Close', 'wp-content-connect')}
				</Button>
				<Button variant="primary" ref={copyRef} __next40pxDefaultSize>
					{__('Copy', 'wp-content-connect')}
				</Button>
			</Flex>
		</Flex>
	);
}

export function DeleteModal({ items, closeModal, onActionPerformed }: RenderModalProps<Row>) {
	const [isDeleting, setIsDeleting] = useState(false);
	const { deleteEntityRecord } = useDispatch(coreStore);
	const { createSuccessNotice, createErrorNotice } = useDispatch(noticesStore);

	const message =
		items.length === 1
			? sprintf(
					/* translators: %s: relationship label */
					__('Delete “%s”?', 'wp-content-connect'),
					items[0].label,
				)
			: sprintf(
					/* translators: %d: number of relationships */
					__('Delete %d relationships?', 'wp-content-connect'),
					items.length,
				);

	const onDelete = async () => {
		setIsDeleting(true);

		try {
			await Promise.all(
				items.map((item) =>
					deleteEntityRecord(
						'postType',
						RELATIONSHIP_POST_TYPE,
						item.postId as number,
						{ force: true },
						{ throwOnError: true },
					),
				),
			);

			createSuccessNotice(
				_n(
					'Relationship deleted.',
					'Relationships deleted.',
					items.length,
					'wp-content-connect',
				),
				{ type: 'snackbar' },
			);

			onActionPerformed?.(items);
		} catch (error) {
			createErrorNotice(
				(error as { message?: string })?.message ??
					__('The relationship could not be deleted.', 'wp-content-connect'),
				{ type: 'snackbar' },
			);
		}

		closeModal?.();
	};

	return (
		<Flex direction="column" gap={4}>
			<p className="content-connect-admin__message">
				{message}{' '}
				{__(
					'Connections already made between content are kept, and come back if the same relationship is created again.',
					'wp-content-connect',
				)}
			</p>
			<Flex justify="flex-end">
				<Button
					variant="tertiary"
					onClick={closeModal}
					disabled={isDeleting}
					accessibleWhenDisabled
					__next40pxDefaultSize
				>
					{__('Cancel', 'wp-content-connect')}
				</Button>
				<Button
					variant="primary"
					isDestructive
					onClick={onDelete}
					isBusy={isDeleting}
					disabled={isDeleting}
					accessibleWhenDisabled
					__next40pxDefaultSize
				>
					{__('Delete', 'wp-content-connect')}
				</Button>
			</Flex>
		</Flex>
	);
}
