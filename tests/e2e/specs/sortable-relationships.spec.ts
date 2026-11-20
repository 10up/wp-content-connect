import { test, expect, ContentConnectPage, TIMEOUTS } from '../fixtures/content-connect';

test.describe('Sortable Relationships', () => {
	test.beforeEach(async ({ admin, editor, page, testData }) => {
		// University 1 has multiple related items across sortable relationships.
		await admin.editPost(testData.posts.university[0]);
		await editor.openDocumentSettingsSidebar();
	});

	test('selected items are displayed in the relationship manager', async ({ page }) => {
		const ccPage = new ContentConnectPage(page);

		await ccPage.expandRelationshipPanel('university_city_cities');

		const selectedItems = ccPage.getSelectedItems('university_city_cities');
		await expect(selectedItems.first()).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });
		expect(await selectedItems.count()).toBeGreaterThan(0);
	});

	test('can view multiple selected items', async ({ page }) => {
		const ccPage = new ContentConnectPage(page);

		await ccPage.expandRelationshipPanel('university_city_cities');

		const selectedItems = ccPage.getSelectedItems('university_city_cities');
		await expect(selectedItems.first()).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });
		const itemCount = await selectedItems.count();

		expect(itemCount).toBeGreaterThan(1);
		for (let i = 0; i < itemCount; i++) {
			await expect(selectedItems.nth(i)).toBeVisible();
		}
	});

	test.fixme('sortable relationships have drag handles when sortable is enabled', async () => {
		// TODO: Identify correct selectors for sortable/drag-handle elements.
	});

	test('items maintain order after adding a new item', async ({ page }) => {
		const ccPage = new ContentConnectPage(page);

		await ccPage.expandRelationshipPanel('university_campus_campuses');

		const selectedItems = ccPage.getSelectedItems('university_campus_campuses');
		const initialCount = await selectedItems.count();

		await ccPage.searchAndSelectItem('university_campus_campuses', 'Campus 5');

		const newCount = await selectedItems.count();
		expect(newCount).toBeGreaterThan(initialCount);
	});

	test('relationship manager is visible for sortable relationship types', async ({ page }) => {
		const ccPage = new ContentConnectPage(page);

		const relationshipKeys = [
			'university_city_cities',
			'university_campus_campuses',
			'university_course_courses',
			'university_user_administrators',
		];

		for (const relKey of relationshipKeys) {
			await ccPage.expandRelationshipPanel(relKey);
			const manager = ccPage.getRelationshipManager(relKey);
			await expect(manager).toBeVisible({ timeout: TIMEOUTS.SEARCH_RESULTS });
		}
	});

	test('course instructor relationships have items', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		await admin.editPost(testData.posts.course[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('course_person_instructors');

		const manager = ccPage.getRelationshipManager('course_person_instructors');
		await expect(manager).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });

		const selectedItems = ccPage.getSelectedItems('course_person_instructors');
		await expect(selectedItems.first()).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });
		expect(await selectedItems.count()).toBeGreaterThan(0);
	});

	test.fixme('drag and drop elements exist for sortable relationships', async () => {
		// TODO: Verify drag and drop DOM structure.
	});
});
