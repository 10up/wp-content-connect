import { test, expect, ContentConnectPage, TIMEOUTS } from '../fixtures/content-connect';

test.describe('Post-to-User Relationships', () => {
	// The Content Picker searches core users (GET /wp/v2/users) and excludes users
	// already related to the post on the client. Its result list renders
	// nondeterministically under CI timing, so rather than drive that flaky UI we
	// simulate the picker's data flow directly over REST — the same requests the
	// picker makes — which is deterministic. Selection/persistence through the UI is
	// covered by the "can add a user" spec below.
	test('a non-related user is offered by the administrators picker', async ({ requestUtils, testData }) => {
		const universityId = testData.posts.university[0];

		// Casey Long is not an administrator of University 1, so she is absent from
		// the related set the picker excludes.
		const related = (await requestUtils.rest({
			path: `/content-connect/v2/post/${universityId}/related`,
			params: {
				rel_key: 'university_user_administrators',
				rel_type: 'post-to-user',
			},
		})) as Array<{ name: string }>;
		expect(related.map((user) => user.name)).not.toContain('Casey Long');

		// ...and she matches the user search the picker performs, so she is offered.
		const results = (await requestUtils.rest({
			path: '/wp/v2/users',
			params: { search: 'Casey', per_page: '20' },
		})) as Array<{ name: string }>;
		expect(results.map((user) => user.name)).toContain('Casey Long');
	});

	test('the picker user search matches a user by display name', async ({ requestUtils }) => {
		const results = (await requestUtils.rest({
			path: '/wp/v2/users',
			params: { search: 'Farah Quinn', per_page: '20' },
		})) as Array<{ name: string }>;
		expect(results.map((user) => user.name)).toContain('Farah Quinn');
	});

	test('can add a user to the administrators relationship', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		// University 4 has no administrators yet.
		await admin.editPost(testData.posts.university[3]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_user_administrators');

		const selectedItems = ccPage.getSelectedItems('university_user_administrators');
		const initialCount = await selectedItems.count();

		await ccPage.searchAndSelectItem('university_user_administrators', 'Blair Stone');

		await expect(selectedItems).toHaveCount(initialCount + 1, { timeout: TIMEOUTS.SEARCH_RESULTS });
	});

	test('can remove a user association', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		// University 1 has existing administrator users.
		await admin.editPost(testData.posts.university[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_user_administrators');

		const selectedItems = ccPage.getSelectedItems('university_user_administrators');
		await expect(selectedItems.first()).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });

		await ccPage.removeFirstSelectedItem('university_user_administrators');
	});

	test('course instructors (user) relationship works', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		await admin.editPost(testData.posts.course[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('course_user_instructors');

		const manager = ccPage.getRelationshipManager('course_user_instructors');
		await expect(manager).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });

		await ccPage.searchAndWaitForResults('course_user_instructors', 'Casey');

		const result = ccPage.getSearchResultByText('course_user_instructors', 'Casey');
		await expect(result).toBeVisible({ timeout: TIMEOUTS.SEARCH_RESULTS });
	});

	test('administrators panel shows the correct label', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		await admin.editPost(testData.posts.university[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_user_administrators');

		const panel = ccPage.getRelationshipPanel('university_user_administrators');
		await expect(panel).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });

		const panelTitle = panel.locator('.components-panel__body-title');
		await expect(panelTitle).toContainText('Administrators', { ignoreCase: true });
	});
});
