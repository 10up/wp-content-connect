/**
 * @jest-environment node
 */
import apiFetch from '@wordpress/api-fetch';
import {
	CONTENT_CONNECT_ENDPOINT,
	getRelationships,
	getRelatedEntities,
	getAllRelatedEntities,
	updateRelatedEntities,
} from './api';

jest.mock('@wordpress/api-fetch');

const mockApiFetch = apiFetch as unknown as jest.Mock;

beforeEach(() => {
	mockApiFetch.mockReset();
	jest.spyOn(console, 'error').mockImplementation(() => {});
});

afterEach(() => {
	(console.error as jest.Mock).mockRestore();
});

describe('getRelationships', () => {
	it('requests the relationships path with query args and returns the payload', async () => {
		const payload = { 'post-post-basic': { rel_key: 'post-post-basic' } };
		mockApiFetch.mockResolvedValue(payload);

		const result = await getRelationships(5, { rel_type: 'post-to-post' });

		expect(mockApiFetch).toHaveBeenCalledTimes(1);
		const { path } = mockApiFetch.mock.calls[0][0];
		expect(path).toContain(`${CONTENT_CONNECT_ENDPOINT}/post/5/relationships`);
		expect(path).toContain('rel_type=post-to-post');
		expect(result).toBe(payload);
	});

	it('rethrows and logs when the request fails', async () => {
		mockApiFetch.mockRejectedValue(new Error('boom'));

		await expect(getRelationships(5)).rejects.toThrow('boom');
		expect(console.error).toHaveBeenCalled();
	});
});

describe('getRelatedEntities', () => {
	it('requests the related path with rel_key and rel_type', async () => {
		mockApiFetch.mockResolvedValue([]);

		await getRelatedEntities(7, { rel_key: 'k', rel_type: 'post-to-post' });

		const { path } = mockApiFetch.mock.calls[0][0];
		expect(path).toContain(`${CONTENT_CONNECT_ENDPOINT}/post/7/related`);
		expect(path).toContain('rel_key=k');
		expect(path).toContain('rel_type=post-to-post');
	});

	it('rethrows and logs when the request fails', async () => {
		mockApiFetch.mockRejectedValue(new Error('boom'));

		await expect(
			getRelatedEntities(7, { rel_key: 'k', rel_type: 'post-to-post' }),
		).rejects.toThrow('boom');
		expect(console.error).toHaveBeenCalled();
	});
});

describe('getAllRelatedEntities', () => {
	it('paginates until X-WP-TotalPages is reached and concatenates results', async () => {
		const page1 = [{ id: 1, name: 'a', type: 'post', uuid: 'u1' }];
		const page2 = [{ id: 2, name: 'b', type: 'post', uuid: 'u2' }];

		mockApiFetch
			.mockResolvedValueOnce({
				json: jest.fn().mockResolvedValue(page1),
				headers: { get: jest.fn().mockReturnValue('2') },
			})
			.mockResolvedValueOnce({
				json: jest.fn().mockResolvedValue(page2),
				headers: { get: jest.fn().mockReturnValue('2') },
			});

		const result = await getAllRelatedEntities(9, { rel_key: 'k', rel_type: 'post-to-post' });

		expect(mockApiFetch).toHaveBeenCalledTimes(2);
		expect(mockApiFetch.mock.calls[0][0]).toMatchObject({ parse: false });
		expect(result).toEqual([...page1, ...page2]);
	});

	it('stops after a single page when only one page exists', async () => {
		mockApiFetch.mockResolvedValueOnce({
			json: jest.fn().mockResolvedValue([]),
			headers: { get: jest.fn().mockReturnValue('1') },
		});

		const result = await getAllRelatedEntities(9, { rel_key: 'k', rel_type: 'post-to-post' });

		expect(mockApiFetch).toHaveBeenCalledTimes(1);
		expect(result).toEqual([]);
	});

	it('defaults to a single page when the total-pages header is absent', async () => {
		mockApiFetch.mockResolvedValueOnce({
			json: jest.fn().mockResolvedValue([{ id: 1, name: 'a', type: 'post', uuid: 'u1' }]),
			headers: { get: jest.fn().mockReturnValue(null) },
		});

		const result = await getAllRelatedEntities(9, { rel_key: 'k', rel_type: 'post-to-post' });

		expect(mockApiFetch).toHaveBeenCalledTimes(1);
		expect(result).toHaveLength(1);
	});

	it('rethrows and logs when a page request fails', async () => {
		mockApiFetch.mockRejectedValue(new Error('boom'));

		await expect(
			getAllRelatedEntities(9, { rel_key: 'k', rel_type: 'post-to-post' }),
		).rejects.toThrow('boom');
		expect(console.error).toHaveBeenCalled();
	});
});

describe('updateRelatedEntities', () => {
	it('POSTs related_ids with rel_key and rel_type query args', async () => {
		mockApiFetch.mockResolvedValue([]);

		await updateRelatedEntities(3, 'mykey', 'post-to-post', [11, 12]);

		const args = mockApiFetch.mock.calls[0][0];
		expect(args.method).toBe('POST');
		expect(args.data).toEqual({ related_ids: [11, 12] });
		expect(args.path).toContain(`${CONTENT_CONNECT_ENDPOINT}/post/3/related`);
		expect(args.path).toContain('rel_key=mykey');
		expect(args.path).toContain('rel_type=post-to-post');
	});

	it('rethrows and logs when the request fails', async () => {
		mockApiFetch.mockRejectedValue(new Error('boom'));

		await expect(updateRelatedEntities(3, 'mykey', 'post-to-post', [11])).rejects.toThrow(
			'boom',
		);
		expect(console.error).toHaveBeenCalled();
	});
});
