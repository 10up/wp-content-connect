/**
 * @jest-environment node
 */
import { LOCKED_FIELDS, getForm, getSections } from './fields';

// The custom controls render components that need a browser; the sections don't use them.
jest.mock('./NameControl', () => ({ NameControl: () => null }));
jest.mock('./PostTypesControl', () => ({ PostTypesControl: () => null }));

const ids = (sections: ReturnType<typeof getSections>) => sections.map((section) => section.id);

describe('getSections', () => {
	it('has general, post type and related sections for post-to-post', () => {
		expect(ids(getSections('post-to-post', false))).toEqual(['general', 'from', 'to']);
	});

	it('drops the related section for post-to-user', () => {
		expect(ids(getSections('post-to-user', false))).toEqual(['general', 'from']);
	});

	it('includes every field when creating', () => {
		const fields = getSections('post-to-post', false).flatMap((section) => section.fields);

		expect(fields).toEqual(expect.arrayContaining(LOCKED_FIELDS));
		expect(fields).toContain('title');
	});

	it('leaves the fields that identify stored connections out when editing', () => {
		const fields = getSections('post-to-post', true).flatMap((section) => section.fields);

		LOCKED_FIELDS.forEach((id) => expect(fields).not.toContain(id));
		expect(fields).toEqual(expect.arrayContaining(['title', 'from_enable_ui', 'to_enable_ui']));
	});
});

describe('getForm', () => {
	it('lays the fields out with labels above the controls', () => {
		expect(getForm(['title'])).toEqual({
			layout: { type: 'regular', labelPosition: 'top' },
			fields: ['title'],
		});
	});
});
