/**
 * @jest-environment node
 */
import { rowToPhp, toPhpValue } from './to-php';
import { Row } from '../types';

const row: Row = {
	id: 'custom-1',
	source: 'custom',
	postId: 1,
	label: 'Cars and tires',
	relType: 'post-to-post',
	relKey: 'car_tire_car-tires',
	relName: 'car-tires',
	from: 'car',
	to: ['tire'],
	fromArgs: { enable_ui: true, sortable: true, max_items: 5, labels: { name: "Driver's tires" } },
	toArgs: { enable_ui: false, sortable: false, max_items: 100, labels: { name: '' } },
	status: 'active',
	bidirectional: false,
};

describe('toPhpValue', () => {
	it('formats scalars and lists', () => {
		expect(toPhpValue("it's")).toBe("'it\\'s'");
		expect(toPhpValue(3)).toBe('3');
		expect(toPhpValue(false)).toBe('false');
		expect(toPhpValue(['a', 'b'])).toBe("array( 'a', 'b' )");
	});

	it('formats objects with aligned keys', () => {
		expect(toPhpValue({ a: 1, long_key: true })).toBe(
			"array(\n\t'a'        => 1,\n\t'long_key' => true,\n)",
		);
	});
});

describe('rowToPhp', () => {
	it('generates a post-to-post relationship', () => {
		const php = rowToPhp(row);

		expect(php).toContain("add_action(\n\t'tenup-content-connect-init',");
		expect(php).toContain(
			"$registry->define_post_to_post(\n\t\t\t'car',\n\t\t\tarray( 'tire' ),\n\t\t\t'car-tires',",
		);
		expect(php).toContain("'name' => 'Driver\\'s tires',");
		// An empty panel title is left out so the name is used.
		expect(php.match(/'labels'/g)).toHaveLength(1);
	});

	it('generates a post-to-user relationship without related types', () => {
		const php = rowToPhp({ ...row, relType: 'post-to-user', to: [] });

		expect(php).toContain("$registry->define_post_to_user(\n\t\t\t'car',\n\t\t\t'car-tires',");
		expect(php).not.toContain("'to'");
	});
});
