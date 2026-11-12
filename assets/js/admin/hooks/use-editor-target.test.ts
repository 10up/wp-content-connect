/**
 * @jest-environment node
 */
import { resolveEditorState } from './use-editor-target';
import { Row } from '../types';

const customRow: Row = {
	id: 'custom-7',
	source: 'custom',
	postId: 7,
	label: 'Cars and tires',
	relType: 'post-to-post',
	relKey: 'car_tire_car-tires',
	relName: 'car-tires',
	from: 'car',
	to: ['tire'],
	fromArgs: { enable_ui: true, sortable: false, max_items: 100 },
	toArgs: { enable_ui: false, sortable: false, max_items: 100 },
	status: 'active',
	bidirectional: false,
};

const codeRow: Row = { ...customRow, id: 'code-car_tire_basic', source: 'code', postId: undefined };

describe('resolveEditorState', () => {
	it('opens an empty form for a new relationship, even while rows load', () => {
		const state = resolveEditorState({ type: 'new' }, [], true);

		expect(state).toMatchObject({ status: 'ready', key: 'new' });
	});

	it('waits for the rows before opening an existing relationship', () => {
		expect(resolveEditorState({ type: 'edit', postId: 7 }, [], true)).toEqual({
			status: 'loading',
		});
	});

	it('opens a custom relationship with its values', () => {
		const state = resolveEditorState({ type: 'edit', postId: 7 }, [customRow], false);

		expect(state).toMatchObject({
			status: 'ready',
			key: 'edit-7',
			postId: 7,
			initialValues: { title: 'Cars and tires', rel_name: 'car-tires', rel_from: 'car' },
		});
	});

	it('reports a relationship that no longer exists', () => {
		expect(resolveEditorState({ type: 'edit', postId: 99 }, [customRow], false)).toEqual({
			status: 'missing',
		});
		expect(resolveEditorState({ type: 'duplicate', rowId: 'code-x' }, [], false)).toEqual({
			status: 'missing',
		});
	});

	it('duplicates any relationship as a new one with a copy label and no name', () => {
		const state = resolveEditorState(
			{ type: 'duplicate', rowId: 'code-car_tire_basic' },
			[customRow, codeRow],
			false,
		);

		expect(state).toMatchObject({
			status: 'ready',
			key: 'duplicate-code-car_tire_basic',
			initialValues: { title: 'Cars and tires (copy)', rel_name: '', rel_to: ['tire'] },
		});
		expect(state).not.toHaveProperty('postId');
	});
});
