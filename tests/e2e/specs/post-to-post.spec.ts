import { test, expect, ContentConnectPage, TIMEOUTS } from '../fixtures/content-connect';

test.describe('Post-to-Post Relationships', () => {
	test('can add a city relationship to a university', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		// University 5 has no city relationships yet.
		await admin.editPost(testData.posts.university[4]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_city_cities');

		const selectedItems = ccPage.getSelectedItems('university_city_cities');
		const initialCount = await selectedItems.count();

		await ccPage.searchAndSelectItem('university_city_cities', 'City 1');

		const newCount = await selectedItems.count();
		expect(newCount).toBeGreaterThan(initialCount);
	});

	test('search returns campuses for the university-to-campus relationship', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		await admin.editPost(testData.posts.university[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_campus_campuses');
		await ccPage.searchAndWaitForResults('university_campus_campuses', 'Campus');

		const result = ccPage.getSearchResultByText('university_campus_campuses', 'Campus');
		await expect(result).toBeVisible({ timeout: TIMEOUTS.SEARCH_RESULTS });
	});

	test('search returns courses for the university-to-course relationship', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		await admin.editPost(testData.posts.university[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_course_courses');
		await ccPage.searchAndWaitForResults('university_course_courses', 'Course');

		const result = ccPage.getSearchResultByText('university_course_courses', 'Course');
		await expect(result).toBeVisible({ timeout: TIMEOUTS.SEARCH_RESULTS });
	});

	test('search filters by the correct post type', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		await admin.editPost(testData.posts.university[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_city_cities');
		await ccPage.searchAndWaitForResults('university_city_cities', 'City');

		const result = ccPage.getSearchResultByText('university_city_cities', 'City');
		await expect(result).toBeVisible({ timeout: TIMEOUTS.SEARCH_RESULTS });
	});

	test('can remove an existing city relationship', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		await admin.editPost(testData.posts.university[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_city_cities');

		const selectedItems = ccPage.getSelectedItems('university_city_cities');
		await expect(selectedItems.first()).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });

		await ccPage.removeFirstSelectedItem('university_city_cities');
	});

	test('campus-to-person relationship works', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		await admin.editPost(testData.posts.campus[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('campus_person_people');

		const manager = ccPage.getRelationshipManager('campus_person_people');
		await expect(manager).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });

		await ccPage.searchAndWaitForResults('campus_person_people', 'Person');

		const result = ccPage.getSearchResultByText('campus_person_people', 'Person');
		await expect(result).toBeVisible({ timeout: TIMEOUTS.SEARCH_RESULTS });
	});
});
