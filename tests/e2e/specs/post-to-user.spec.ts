import { test, expect, ContentConnectPage, TIMEOUTS } from '../fixtures/content-connect';

test.describe('Post-to-User Relationships', () => {
	test('can search users for the administrators relationship', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		await admin.editPost(testData.posts.university[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_user_administrators');

		const manager = ccPage.getRelationshipManager('university_user_administrators');
		await expect(manager).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });

		// Casey Long is not an administrator of University 1, so it is not
		// excluded from the ContentPicker search results.
		await ccPage.searchAndWaitForResults('university_user_administrators', 'Casey');

		const result = ccPage.getSearchResultByText('university_user_administrators', 'Casey');
		await expect(result).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });
	});

	test('can search users by display name', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		// University 4 has no administrators, so any user is selectable.
		await admin.editPost(testData.posts.university[3]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_user_administrators');
		await ccPage.searchAndWaitForResults('university_user_administrators', 'Farah Quinn');

		const result = ccPage.getSearchResultByText('university_user_administrators', 'Farah Quinn');
		await expect(result).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });
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
