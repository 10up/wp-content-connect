/**
 * @jest-environment node
 */
import {
	codeToRow,
	emptyFormValues,
	formValuesToRecord,
	recordToRow,
	rowToFormValues,
} from './records';
import { CodeRelationship, CustomRelationshipRecord } from '../types';

const record: CustomRelationshipRecord = {
	id: 7,
	status: 'publish',
	title: { raw: 'Cars and tires' },
	meta: {
		rel_type: 'post-to-post',
		rel_from: 'car',
		rel_to: ['tire'],
		rel_name: 'car-tires',
		from_args: { enable_ui: true, sortable: true, max_items: 5, labels: { name: 'Tires' } },
		to_args: { enable_ui: true, sortable: false, max_items: 10, labels: { name: 'Cars' } },
	},
	rel_key: 'car_tire_car-tires',
	registration_error: null,
};

describe('recordToRow', () => {
	it('maps a published custom relationship to an active custom row', () => {
		const row = recordToRow(record);

		expect(row).toMatchObject({
			id: 'custom-7',
			source: 'custom',
			postId: 7,
			label: 'Cars and tires',
			relKey: 'car_tire_car-tires',
			from: 'car',
			to: ['tire'],
			status: 'active',
			bidirectional: true,
		});
	});

	it('marks drafts as disabled and failed registrations as errors', () => {
		expect(recordToRow({ ...record, status: 'draft' }).status).toBe('disabled');
		expect(recordToRow({ ...record, registration_error: 'Missing type' }).status).toBe('error');
	});

	it('falls back to the name when the label is empty', () => {
		expect(recordToRow({ ...record, title: { raw: '' } }).label).toBe('car-tires');
	});

	it('clears related post types and bidirectionality for post-to-user', () => {
		const row = recordToRow({
			...record,
			meta: { ...record.meta, rel_type: 'post-to-user' },
		});

		expect(row.to).toEqual([]);
		expect(row.bidirectional).toBe(false);
	});
});

describe('codeToRow', () => {
	it('maps a post-to-post relationship registered from code', () => {
		const relationship: CodeRelationship = {
			rel_key: 'car_tire_basic',
			rel_type: 'post-to-post',
			rel_name: 'basic',
			source: 'code',
			post_id: null,
			from: {
				object_type: 'car',
				labels: { name: 'Tires' },
				max_items: 100,
				sortable: false,
				enable_ui: true,
			},
			to: {
				object_types: ['tire'],
				labels: { name: 'Cars' },
				max_items: 100,
				sortable: false,
				enable_ui: false,
			},
		};

		expect(codeToRow(relationship)).toMatchObject({
			id: 'code-car_tire_basic',
			source: 'code',
			label: 'Tires',
			from: 'car',
			to: ['tire'],
			bidirectional: false,
			status: 'active',
		});
	});

	it('maps a post-to-user relationship registered from code', () => {
		const relationship: CodeRelationship = {
			rel_key: 'car_user_owner',
			rel_type: 'post-to-user',
			rel_name: 'owner',
			source: 'code',
			post_id: null,
			post_type: 'car',
			labels: { name: 'Owners' },
			sortable: true,
			enable_ui: true,
			max_items: 3,
		};

		expect(codeToRow(relationship)).toMatchObject({
			from: 'car',
			to: [],
			label: 'Owners',
			fromArgs: { enable_ui: true, sortable: true, max_items: 3 },
		});
	});
});

describe('form values', () => {
	it('round-trips a row through the form values', () => {
		const values = rowToFormValues(recordToRow(record));
		const { title, meta } = formValuesToRecord(values);

		expect(title).toBe('Cars and tires');
		expect(meta).toEqual(record.meta);
	});

	it('drops related post types and the related panel for post-to-user', () => {
		const { meta } = formValuesToRecord({
			...emptyFormValues(),
			rel_type: 'post-to-user',
			rel_from: 'car',
			rel_to: ['tire'],
			to_enable_ui: true,
		});

		expect(meta.rel_to).toEqual([]);
		expect(meta.to_args.enable_ui).toBe(false);
	});

	it('keeps max items at least 1 and trims labels', () => {
		const { meta } = formValuesToRecord({
			...emptyFormValues(),
			from_max_items: 0,
			from_label: '  Tires  ',
		});

		expect(meta.from_args.max_items).toBe(100);
		expect(meta.from_args.labels).toEqual({ name: 'Tires' });
	});
});
