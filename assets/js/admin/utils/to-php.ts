/**
 * Internal dependencies
 */
import { Row, SideArgs } from '../types';

type PhpValue = string | number | boolean | PhpValue[] | { [key: string]: PhpValue };

const quote = (value: string): string => `'${value.replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;

const indent = (depth: number): string => '\t'.repeat(depth);

/**
 * Formats a value as a PHP literal, with `array()` syntax and aligned keys.
 */
export const toPhpValue = (value: PhpValue, depth = 0): string => {
	if (typeof value === 'string') {
		return quote(value);
	}

	if (typeof value === 'number') {
		return String(value);
	}

	if (typeof value === 'boolean') {
		return value ? 'true' : 'false';
	}

	if (Array.isArray(value)) {
		return `array( ${value.map((item) => toPhpValue(item, depth)).join(', ')} )`;
	}

	const keys = Object.keys(value);
	const width = Math.max(...keys.map((key) => quote(key).length));
	const lines = keys.map(
		(key) =>
			`${indent(depth + 1)}${quote(key).padEnd(width)} => ${toPhpValue(value[key], depth + 1)},`,
	);

	return `array(\n${lines.join('\n')}\n${indent(depth)})`;
};

const sideArgsToPhp = (args: SideArgs): { [key: string]: PhpValue } => {
	const result: { [key: string]: PhpValue } = {
		enable_ui: args.enable_ui,
		sortable: args.sortable,
		max_items: args.max_items,
	};

	const label = args.labels?.name?.trim();
	if (label) {
		result.labels = { name: label };
	}

	return result;
};

/**
 * Returns the PHP that registers the relationship from code.
 */
export const rowToPhp = (row: Row): string => {
	const isPostToPost = row.relType === 'post-to-post';

	const args: { [key: string]: PhpValue } = isPostToPost
		? { from: sideArgsToPhp(row.fromArgs), to: sideArgsToPhp(row.toArgs) }
		: { from: sideArgsToPhp(row.fromArgs) };

	const params = isPostToPost
		? [toPhpValue(row.from), toPhpValue(row.to), toPhpValue(row.relName), toPhpValue(args, 3)]
		: [toPhpValue(row.from), toPhpValue(row.relName), toPhpValue(args, 3)];

	const method = isPostToPost ? 'define_post_to_post' : 'define_post_to_user';

	return [
		'add_action(',
		"\t'tenup-content-connect-init',",
		'\tfunction ( $registry ) {',
		`\t\t$registry->${method}(`,
		params.map((param) => `\t\t\t${param}`).join(',\n'),
		'\t\t);',
		'\t}',
		');',
	].join('\n');
};
