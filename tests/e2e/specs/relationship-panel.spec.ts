import { test, expect, ContentConnectPage, TIMEOUTS } from '../fixtures/content-connect';

test.describe('Relationship Panel Visibility', () => {
	test('panel appears in sidebar for post types with enable_ui relationships', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		await admin.editPost(testData.posts.university[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_city_cities');
		const manager = ccPage.getRelationshipManager('university_city_cities');
		await expect(manager).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });
	});

	test('panel hidden for post types without relationships', async ({ admin, editor, page }) => {
		await admin.createNewPost({ postType: 'page' });

		// Dismiss the "Choose a pattern" modal if it appears.
		const closeButton = page.getByRole('dialog').getByRole('button', { name: 'Close' });
		await closeButton.click({ timeout: TIMEOUTS.PANEL_VISIBLE }).catch(() => {});

		await editor.openDocumentSettingsSidebar();

		const relationshipManagers = page.locator('[class*="content-connect-relationship-manager"]');
		await expect(relationshipManagers).toHaveCount(0);
	});

	test('multiple panels shown for a post type with multiple relationships', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		await admin.editPost(testData.posts.university[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_city_cities');
		await ccPage.expandRelationshipPanel('university_campus_campuses');
		await ccPage.expandRelationshipPanel('university_course_courses');

		await expect(ccPage.getRelationshipManager('university_city_cities')).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });
		await expect(ccPage.getRelationshipManager('university_campus_campuses')).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });
		await expect(ccPage.getRelationshipManager('university_course_courses')).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });
	});

	test('panel title is visible for a relationship', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		await admin.editPost(testData.posts.university[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('university_city_cities');
		const panel = ccPage.getRelationshipPanel('university_city_cities');
		const panelTitle = panel.locator('.components-panel__body-title');
		await expect(panelTitle).toBeVisible();
	});

	test('campus post type shows campus-specific relationships', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		await admin.editPost(testData.posts.campus[0]);
		await editor.openDocumentSettingsSidebar();

		await ccPage.expandRelationshipPanel('campus_person_people');
		const manager = ccPage.getRelationshipManager('campus_person_people');
		await expect(manager).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });
	});
});
