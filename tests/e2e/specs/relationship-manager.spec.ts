import { test, expect, ContentConnectPage, TIMEOUTS } from '../fixtures/content-connect';

test.describe('Relationship Manager', () => {
	test('can search for related content via ContentPicker', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		await admin.editPost(testData.posts.university[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_city_cities');

		const searchInput = ccPage.getContentPickerSearchInput('university_city_cities');
		await expect(searchInput).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });

		await ccPage.searchAndWaitForResults('university_city_cities', 'City');

		const result = ccPage.getSearchResultByText('university_city_cities', 'City');
		await expect(result).toBeVisible({ timeout: TIMEOUTS.SEARCH_RESULTS });
	});

	test('can select content from search results', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		// University 4 has no city relationships yet.
		await admin.editPost(testData.posts.university[3]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_city_cities');

		const selectedItems = ccPage.getSelectedItems('university_city_cities');
		const initialCount = await selectedItems.count();

		await ccPage.searchAndSelectItem('university_city_cities', 'City 5');

		await expect(selectedItems).toHaveCount(initialCount + 1, { timeout: TIMEOUTS.SEARCH_RESULTS });
	});

	test('can remove selected content', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		// University 1 has existing city relationships.
		await admin.editPost(testData.posts.university[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_city_cities');

		const selectedItems = ccPage.getSelectedItems('university_city_cities');
		await expect(selectedItems.first()).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });

		await ccPage.removeFirstSelectedItem('university_city_cities');
	});

	test('shows an empty picker for a relationship with no selections', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		// University 4 has no course relationships.
		await admin.editPost(testData.posts.university[3]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_course_courses');

		const manager = ccPage.getRelationshipManager('university_course_courses');
		await expect(manager).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });

		const searchInput = ccPage.getContentPickerSearchInput('university_course_courses');
		await expect(searchInput).toBeVisible();
	});

	test('adding a relationship reflects in the UI', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		// University 5 has no campus relationships.
		await admin.editPost(testData.posts.university[4]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_campus_campuses');

		const selectedItems = ccPage.getSelectedItems('university_campus_campuses');
		const initialCount = await selectedItems.count();

		await ccPage.searchAndSelectItem('university_campus_campuses', 'Campus 1');

		const newCount = await selectedItems.count();
		expect(newCount).toBeGreaterThan(initialCount);
	});
});
