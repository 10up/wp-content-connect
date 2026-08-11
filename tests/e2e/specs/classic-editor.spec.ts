import { test, expect, ContentConnectPage, TIMEOUTS } from '../fixtures/content-connect';

/**
 * Classic editor coverage.
 *
 * The `department` post type ships with the block editor disabled, so editing
 * it exercises Content Connect's classic-editor meta-box path: the manager
 * mounts inside a meta box, and saving goes through the #post form submit
 * interceptor -> persistContentConnectChanges() -> REST, rather than the block
 * editor's preSavePost filter.
 */
test.describe('Classic Editor', () => {
	const REL_KEY = 'department_city_cities';

	test('renders the relationship manager inside a meta box', async ({ page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		// Department 2 has no relationships yet.
		await ccPage.gotoClassicEditor(testData.posts.department[1], REL_KEY);

		const manager = ccPage.getRelationshipManager(REL_KEY);
		await expect(manager).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });

		const searchInput = ccPage.getContentPickerSearchInput(REL_KEY);
		await expect(searchInput).toBeVisible();
	});

	test('loads an existing relationship', async ({ page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		// Department 1 is pre-wired to City 1.
		await ccPage.gotoClassicEditor(testData.posts.department[0], REL_KEY);

		const selectedItems = ccPage.getSelectedItems(REL_KEY);
		await expect(selectedItems.first()).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });
		await expect(selectedItems.filter({ hasText: 'City 1' })).toHaveCount(1);
	});

	test('persists a newly added relationship across a save and reload', async ({ page, testData }) => {
		const ccPage = new ContentConnectPage(page);
		const departmentId = testData.posts.department[1];

		// Department 2 starts with no relationships.
		await ccPage.gotoClassicEditor(departmentId, REL_KEY);

		const selectedItems = ccPage.getSelectedItems(REL_KEY);
		await expect(selectedItems).toHaveCount(0);

		await ccPage.searchAndSelectItem(REL_KEY, 'City 2');
		await expect(selectedItems.filter({ hasText: 'City 2' })).toHaveCount(1, {
			timeout: TIMEOUTS.SEARCH_RESULTS,
		});

		// Save through the classic #post form (the interceptor persists first).
		await ccPage.saveClassicPost();

		// Re-open from scratch so the assertion reflects persisted DB state, not
		// leftover client state.
		await ccPage.gotoClassicEditor(departmentId, REL_KEY);

		const reloadedItems = ccPage.getSelectedItems(REL_KEY);
		await expect(reloadedItems.first()).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });
		await expect(reloadedItems.filter({ hasText: 'City 2' })).toHaveCount(1);
	});
});
