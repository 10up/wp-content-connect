/**
 * @jest-environment node
 */
import { NAME_MAX_LENGTH, sanitizeName } from './name';

describe('sanitizeName', () => {
	it('lowercases and turns spaces into dashes', () => {
		expect(sanitizeName('Related Cars')).toBe('related-cars');
	});

	it('drops characters the server rejects and strips accents', () => {
		expect(sanitizeName('Café & Bar!')).toBe('cafe--bar');
		expect(sanitizeName('car_parts-2')).toBe('car_parts-2');
	});

	it('keeps a trailing dash so the name can be typed', () => {
		expect(sanitizeName('related-')).toBe('related-');
	});

	it('caps the length', () => {
		expect(sanitizeName('a'.repeat(80))).toHaveLength(NAME_MAX_LENGTH);
	});
});
