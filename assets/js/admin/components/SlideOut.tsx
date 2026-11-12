/**
 * External dependencies
 */
import React from 'react';

/**
 * WordPress dependencies
 */
import {
	useConstrainedTabbing,
	useFocusOnMount,
	useFocusReturn,
	useMergeRefs,
} from '@wordpress/compose';

type SlideOutProps = {
	label: string;
	onClose(): void;
	children: React.ReactNode;
};

/**
 * A panel sliding in from the side of the screen, over a scrim.
 *
 * Used where the boot layout, which renders the editor in its own inspector
 * region, is not available.
 */
export function SlideOut({ label, onClose, children }: SlideOutProps) {
	const ref = useMergeRefs([
		useFocusOnMount('firstElement'),
		useFocusReturn(),
		useConstrainedTabbing(),
	]);

	const onKeyDown = (event: React.KeyboardEvent) => {
		if (event.key === 'Escape') {
			event.stopPropagation();
			onClose();
		}
	};

	return (
		<>
			<div className="content-connect-slideout__scrim" onClick={onClose} aria-hidden="true" />
			<div
				ref={ref}
				className="content-connect-slideout"
				role="dialog"
				aria-modal="true"
				aria-label={label}
				onKeyDown={onKeyDown}
				tabIndex={-1}
			>
				{children}
			</div>
		</>
	);
}
