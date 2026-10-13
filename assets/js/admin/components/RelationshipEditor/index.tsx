/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import { Button, Notice, Panel, PanelBody, Spinner } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import { DataForm, useFormValidity } from '@wordpress/dataviews/wp';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
import { store as noticesStore } from '@wordpress/notices';
import { cleanForSlug } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { FormValues } from '../../types';
import { sanitizeName } from '../../utils/name';
import { formValuesToRecord } from '../../utils/records';
import { RELATIONSHIP_POST_TYPE, usePostTypeElements } from '../../hooks/use-post-type-elements';
import { getFields, getForm, getSections } from './fields';
import { LockedSummary } from './LockedSummary';

type RelationshipEditorProps = {
	// Post ID of the custom relationship being edited; omitted when creating one.
	postId?: number;
	initialValues: FormValues;
	onClose(): void;
};

export function RelationshipEditor({ postId, initialValues, onClose }: RelationshipEditorProps) {
	const isEditing = Boolean(postId);
	const headingId = useInstanceId(RelationshipEditor, 'content-connect-editor-title');

	const [values, setValues] = useState<FormValues>(initialValues);
	const [isNameEdited, setIsNameEdited] = useState(isEditing || initialValues.rel_name !== '');
	const [isSaving, setIsSaving] = useState(false);
	const [error, setError] = useState<string | null>(null);

	const [hasResolvedPostTypes, postTypes] = usePostTypeElements();
	const { saveEntityRecord, invalidateResolutionForStoreSelector } = useDispatch(coreStore);
	const { createSuccessNotice } = useDispatch(noticesStore);

	const fields = useMemo(() => getFields(postTypes), [postTypes]);
	const sections = useMemo(
		() => getSections(values.rel_type, isEditing),
		[values.rel_type, isEditing],
	);
	// Validity covers every section, so one invalid field anywhere blocks saving.
	const form = useMemo(() => getForm(sections.flatMap((section) => section.fields)), [sections]);
	const { validity, isValid } = useFormValidity(values, fields, form);

	const title = isEditing
		? __('Edit relationship', 'wp-content-connect')
		: __('Add relationship', 'wp-content-connect');

	const onChange = (edits: Partial<FormValues>) => {
		if ('rel_name' in edits) {
			setIsNameEdited(true);
		}

		setValues((current) => {
			const next = { ...current, ...edits };

			// Suggest a name from the label until the name is edited by hand.
			if (!isNameEdited && 'title' in edits) {
				next.rel_name = sanitizeName(cleanForSlug(next.title));
			}

			return next;
		});
	};

	const onSubmit = async (event: React.FormEvent) => {
		event.preventDefault();

		setIsSaving(true);
		setError(null);

		const { title: label, meta } = formValuesToRecord(values);
		const record = isEditing
			? { id: postId, title: label, meta }
			: { title: label, meta, status: 'publish' };

		try {
			await saveEntityRecord('postType', RELATIONSHIP_POST_TYPE, record, {
				throwOnError: true,
			});

			// A new record is not part of the cached list query until it is refetched.
			if (!isEditing) {
				invalidateResolutionForStoreSelector('getEntityRecords');
			}

			createSuccessNotice(
				__(
					'Relationship saved. Editor panels reflect it on the next page load.',
					'wp-content-connect',
				),
				{ type: 'snackbar' },
			);

			onClose();
		} catch (saveError) {
			setError(
				(saveError as { message?: string })?.message ??
					__('The relationship could not be saved.', 'wp-content-connect'),
			);
			setIsSaving(false);
		}
	};

	return (
		<form className="content-connect-editor" onSubmit={onSubmit} aria-labelledby={headingId}>
			<div className="content-connect-editor__header">
				<h2 id={headingId} className="content-connect-editor__title">
					{title}
				</h2>
				<Button
					icon={closeSmall}
					label={__('Close', 'wp-content-connect')}
					onClick={onClose}
					size="compact"
				/>
			</div>

			<div className="content-connect-editor__content">
				{!hasResolvedPostTypes ? (
					<div className="content-connect-editor__notices">
						<Spinner />
					</div>
				) : (
					<>
						{(error || isEditing) && (
							<div className="content-connect-editor__notices">
								{error && (
									<Notice status="error" isDismissible={false}>
										{error}
									</Notice>
								)}

								{isEditing && (
									<LockedSummary values={values} postTypes={postTypes} />
								)}
							</div>
						)}

						<Panel className="content-connect-editor__panel">
							{sections.map((section) => (
								<PanelBody key={section.id} title={section.title} initialOpen>
									<DataForm<FormValues>
										data={values}
										fields={fields}
										form={getForm(section.fields)}
										validity={validity}
										onChange={onChange}
									/>
								</PanelBody>
							))}
						</Panel>
					</>
				)}
			</div>

			<div className="content-connect-editor__footer">
				<Button variant="tertiary" onClick={onClose} __next40pxDefaultSize>
					{__('Cancel', 'wp-content-connect')}
				</Button>
				<Button
					variant="primary"
					type="submit"
					isBusy={isSaving}
					disabled={!hasResolvedPostTypes || !isValid || isSaving}
					accessibleWhenDisabled
					__next40pxDefaultSize
				>
					{__('Save', 'wp-content-connect')}
				</Button>
			</div>
		</form>
	);
}
