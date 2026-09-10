import { test, expect, TIMEOUTS } from '../fixtures/content-connect';

/**
 * Query Loop (core/query) relationship support.
 *
 * The seeded data wires University 1 to Course 1 and Course 2 (and to nothing
 * else); Course 3 belongs to University 3. So a Query Loop that queries the
 * `course` post type and is filtered to University 1's related entities must
 * show Course 1 and Course 2 while excluding the unrelated Course 3.
 *
 * These specs edit University 1 directly, which makes it the default source
 * post for the block extension (currentPostId), so no source-post picker
 * interaction is required. Nothing is saved: each test reloads a clean editor.
 */
test.describe('Query Loop relationship support', () => {
	const RELATED_TOGGLE = 'Only show related entities';

	/**
	 * Inserts a Query Loop that lists courses, with a Post Template + Post Title
	 * so the previewed posts render their titles in the editor canvas.
	 */
	async function insertCourseQueryLoop(editor: any): Promise<void> {
		await editor.insertBlock({
			name: 'core/query',
			attributes: {
				query: {
					perPage: 10,
					offset: 0,
					postType: 'course',
					order: 'asc',
					orderBy: 'title',
					inherit: false,
				},
			},
			innerBlocks: [
				{
					name: 'core/post-template',
					innerBlocks: [{ name: 'core/post-title' }],
				},
			],
		});
	}

	test('filters the Query Loop preview to related posts', async ({
		admin,
		editor,
		page,
		testData,
	}) => {
		await admin.editPost(testData.posts.university[0]);

		await insertCourseQueryLoop(editor);
		await editor.openDocumentSettingsSidebar();

		// Unfiltered: the unrelated Course 3 is present in the preview.
		await expect(
			editor.canvas.getByText('Course 3', { exact: true }).first()
		).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });

		// Enable relationship filtering (default source post is University 1).
		await page.getByLabel(RELATED_TOGGLE).check();

		// Related courses remain visible.
		await expect(
			editor.canvas.getByText('Course 1', { exact: true }).first()
		).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });
		await expect(
			editor.canvas.getByText('Course 2', { exact: true }).first()
		).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });

		// The unrelated course is filtered out.
		await expect(
			editor.canvas.getByText('Course 3', { exact: true })
		).toHaveCount(0, { timeout: TIMEOUTS.PANEL_VISIBLE });
	});

	test('restores the full list when relationship filtering is disabled', async ({
		admin,
		editor,
		page,
		testData,
	}) => {
		await admin.editPost(testData.posts.university[0]);

		await insertCourseQueryLoop(editor);
		await editor.openDocumentSettingsSidebar();

		const relatedToggle = page.getByLabel(RELATED_TOGGLE);

		// Turn filtering on, confirm the unrelated course is gone.
		await relatedToggle.check();
		await expect(
			editor.canvas.getByText('Course 3', { exact: true })
		).toHaveCount(0, { timeout: TIMEOUTS.PANEL_VISIBLE });

		// Turn filtering off, the unrelated course comes back.
		await relatedToggle.uncheck();
		await expect(
			editor.canvas.getByText('Course 3', { exact: true }).first()
		).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });
	});
});
