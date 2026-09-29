/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

type RouteContext = {
	search: Record<string, unknown>;
};

export const route = {
	title: () => __('Content Connect', 'wp-content-connect'),
	// The editor panel shows while a relationship is being added, edited or duplicated.
	inspector: ({ search }: RouteContext) => Boolean(search.edit || search.duplicate),
};
