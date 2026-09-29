import { test, expect, ContentConnectPage, TIMEOUTS } from '../fixtures/content-connect';
import type { Page } from '@playwright/test';
import { wpCli } from '../utils/helpers';

const ADMIN_PAGE = '/wp-admin/options-general.php?page=content-connect';

// The editor opens in the side panel as a form named after its heading.
const editorPanel = (page: Page) => page.getByRole('form', { name: /relationship$/ });

type CustomRelationshipMeta = {
	rel_type?: string;
	rel_from: string;
	rel_to?: string[];
	rel_name: string;
	from_args?: Record<string, unknown>;
	to_args?: Record<string, unknown>;
};

/**
 * Creates a custom relationship directly, bypassing the screen.
 */
const createCustomRelationship = (
	title: string,
	meta: CustomRelationshipMeta,
	status: 'publish' | 'draft' = 'publish',
) => {
	const metaInput = JSON.stringify({ rel_type: 'post-to-post', ...meta });

	wpCli(
		`post create --post_type=cc_relationship --post_status=${status} --post_title="${title}" --meta_input='${metaInput}'`,
	);
};

/**
 * Opens a row's actions menu and picks an action.
 */
const runRowAction = async (page: Page, rowName: RegExp, action: string) => {
	const row = page.getByRole('row', { name: rowName });

	await row.getByRole('button', { name: 'Actions' }).click();
	await page.getByRole('menuitem', { name: action }).click();
};

test.describe('Relationships admin screen', () => {
	test.afterEach(() => {
		wpCli(
			`eval 'foreach ( get_posts( array( "post_type" => "cc_relationship", "post_status" => "any", "numberposts" => -1, "fields" => "ids" ) ) as $id ) { wp_delete_post( $id, true ); }'`,
		);
	});

	test('lists relationships registered from code as read-only', async ({ page }) => {
		await page.goto(ADMIN_PAGE);

		const row = page.getByRole('row', { name: /Related Cities/ }).first();
		await expect(row).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });
		await expect(row.getByRole('cell', { name: 'Code', exact: true })).toBeVisible();
		await expect(row.getByRole('button', { name: 'Edit' })).toHaveCount(0);
	});

	test('creates a bidirectional relationship that adds panels to both post types', async ({
		admin,
		editor,
		page,
		testData,
	}) => {
		const ccPage = new ContentConnectPage(page);

		await page.goto(ADMIN_PAGE);
		await page.getByRole('button', { name: 'Add new Relationship' }).click();

		const dialog = editorPanel(page);
		await dialog.getByLabel('Label').fill('City residents');
		await expect(dialog.getByLabel('Name')).toHaveValue('city-residents');
		await dialog
			.getByRole('combobox', { name: /^Post type/ })
			.selectOption({ label: 'Cities' });
		await dialog.getByLabel('Panel title').first().fill('Residents');

		await dialog.getByRole('checkbox', { name: 'People' }).check();

		await dialog.getByLabel('Also show a panel on related posts (bidirectional)').check();
		await dialog.getByLabel('Panel title').nth(1).fill('Home cities');

		await dialog.getByRole('button', { name: 'Save' }).click();
		await expect(dialog).toHaveCount(0);

		const row = page.getByRole('row', { name: /City residents/ });
		await expect(row.getByRole('cell', { name: 'Custom', exact: true })).toBeVisible();

		await admin.editPost(testData.posts.city[0]);
		await editor.openDocumentSettingsSidebar();
		await ccPage.expandRelationshipPanel('city_person_city-residents');
		await expect(ccPage.getRelationshipManager('city_person_city-residents')).toBeVisible({
			timeout: TIMEOUTS.PANEL_VISIBLE,
		});

		await admin.editPost(testData.posts.person[0]);
		await editor.openDocumentSettingsSidebar();
		await ccPage.expandRelationshipPanel('city_person_city-residents');
		await expect(ccPage.getRelationshipManager('city_person_city-residents')).toBeVisible({
			timeout: TIMEOUTS.PANEL_VISIBLE,
		});
	});

	test('rejects a relationship that duplicates one registered from code', async ({ page }) => {
		await page.goto(ADMIN_PAGE);
		await page.getByRole('button', { name: 'Add new Relationship' }).click();

		const dialog = editorPanel(page);
		await dialog.getByLabel('Label').fill('Duplicate');
		await dialog.getByLabel('Name').fill('cities');
		await dialog
			.getByRole('combobox', { name: /^Post type/ })
			.selectOption({ label: 'Universities' });
		await dialog.getByRole('checkbox', { name: 'Cities' }).check();

		await dialog.getByRole('button', { name: 'Save' }).click();

		await expect(dialog.getByText('already exists')).toBeVisible();
	});

	test('disabling a relationship removes its editor panel', async ({
		admin,
		editor,
		page,
		testData,
	}) => {
		const meta = JSON.stringify({
			rel_type: 'post-to-post',
			rel_from: 'city',
			rel_to: ['person'],
			rel_name: 'disabled-residents',
		});

		wpCli(
			`post create --post_type=cc_relationship --post_status=draft --post_title="Disabled residents" --meta_input='${meta}'`,
		);

		await admin.editPost(testData.posts.city[0]);
		await editor.openDocumentSettingsSidebar();

		await expect(
			page.locator('.content-connect-relationship-manager-city_person_disabled-residents'),
		).toHaveCount(0);
	});

	test('summarizes the fields that identify stored connections when editing', async ({
		page,
	}) => {
		const meta = JSON.stringify({
			rel_type: 'post-to-post',
			rel_from: 'city',
			rel_to: ['person'],
			rel_name: 'locked-residents',
		});

		wpCli(
			`post create --post_type=cc_relationship --post_status=publish --post_title="Locked residents" --meta_input='${meta}'`,
		);

		await page.goto(ADMIN_PAGE);
		await page
			.getByRole('row', { name: /Locked residents/ })
			.getByRole('button', { name: 'Edit' })
			.click();

		const panel = editorPanel(page);
		await expect(panel.getByLabel('Label')).toHaveValue('Locked residents');

		const note = panel.locator('.content-connect-admin__locked');
		await expect(note).toContainText('locked-residents');
		await expect(note).toContainText('Cities');
		await expect(note).toContainText('People');

		await expect(panel.getByLabel('Name')).toHaveCount(0);
		await expect(panel.getByRole('radio', { name: 'Posts to posts' })).toHaveCount(0);
		await expect(panel.getByRole('checkbox', { name: 'People' })).toHaveCount(0);
	});

	test('opens the editor from the URL and closes it', async ({ page }) => {
		await page.goto(`${ADMIN_PAGE}&p=${encodeURIComponent('/?edit=new')}`);

		const panel = editorPanel(page);
		await expect(panel).toBeVisible({ timeout: TIMEOUTS.PANEL_VISIBLE });

		await panel.getByRole('button', { name: 'Close' }).click();

		await expect(panel).toHaveCount(0);
		await expect(page).not.toHaveURL(/edit%3Dnew|edit=new/);
	});

	test('restricts the name to allowed characters while typing', async ({ page }) => {
		await page.goto(ADMIN_PAGE);
		await page.getByRole('button', { name: 'Add new Relationship' }).click();

		const name = editorPanel(page).getByLabel('Name');
		await name.pressSequentially('My Name!');

		await expect(name).toHaveValue('my-name');
	});

	test('disables and enables a relationship from the list', async ({ page }) => {
		createCustomRelationship('Toggled residents', {
			rel_from: 'city',
			rel_to: ['person'],
			rel_name: 'toggled-residents',
		});

		await page.goto(ADMIN_PAGE);

		const row = page.getByRole('row', { name: /Toggled residents/ });
		await expect(row.getByRole('cell', { name: 'Active', exact: true })).toBeVisible({
			timeout: TIMEOUTS.PANEL_VISIBLE,
		});

		await runRowAction(page, /Toggled residents/, 'Disable');
		await expect(row.getByRole('cell', { name: 'Disabled', exact: true })).toBeVisible();

		await runRowAction(page, /Toggled residents/, 'Enable');
		await expect(row.getByRole('cell', { name: 'Active', exact: true })).toBeVisible();
	});

	test('deletes a relationship after confirming', async ({ page }) => {
		createCustomRelationship('Deleted residents', {
			rel_from: 'city',
			rel_to: ['person'],
			rel_name: 'deleted-residents',
		});

		await page.goto(ADMIN_PAGE);
		await expect(page.getByRole('row', { name: /Deleted residents/ })).toBeVisible({
			timeout: TIMEOUTS.PANEL_VISIBLE,
		});

		await runRowAction(page, /Deleted residents/, 'Delete');

		const dialog = page.getByRole('dialog', { name: 'Delete relationship' });
		await expect(dialog).toContainText('Connections already made between content are kept');
		await dialog.getByRole('button', { name: 'Delete' }).click();

		await expect(page.getByRole('row', { name: /Deleted residents/ })).toHaveCount(0);
		expect(
			wpCli('post list --post_type=cc_relationship --post_status=any --format=count'),
		).toMatch(/\b0\b/);
	});

	test('duplicates a relationship into a new one', async ({ page }) => {
		createCustomRelationship('Original residents', {
			rel_from: 'city',
			rel_to: ['person'],
			rel_name: 'original-residents',
		});

		await page.goto(ADMIN_PAGE);
		await expect(page.getByRole('row', { name: /Original residents/ })).toBeVisible({
			timeout: TIMEOUTS.PANEL_VISIBLE,
		});

		await runRowAction(page, /Original residents/, 'Duplicate');

		const panel = editorPanel(page);
		await expect(panel.getByRole('heading', { name: 'Add relationship' })).toBeVisible();
		await expect(panel.getByLabel('Label')).toHaveValue('Original residents (copy)');
		await expect(panel.getByLabel('Name')).toHaveValue('');
		await expect(panel.getByRole('checkbox', { name: 'People' })).toBeChecked();

		await panel.getByLabel('Name').fill('copied-residents');
		await panel.getByRole('button', { name: 'Save' }).click();

		await expect(panel).toHaveCount(0);
		await expect(page.getByRole('row', { name: /Original residents \(copy\)/ })).toBeVisible();
	});

	test('copies a relationship as PHP', async ({ page }) => {
		await page.goto(ADMIN_PAGE);
		await expect(page.getByRole('row', { name: /Course Instructors/ }).first()).toBeVisible({
			timeout: TIMEOUTS.PANEL_VISIBLE,
		});

		await runRowAction(page, /Course Instructors/, 'Copy as PHP');

		const code = page.getByRole('dialog', { name: 'Copy as PHP' }).getByRole('textbox');
		await expect(code).toHaveValue(/\$registry->define_post_to_post\(/);
		await expect(code).toHaveValue(/'course',\s+array\( 'person' \),\s+'instructors'/);
	});

	test('saves changes to an existing relationship', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		createCustomRelationship('Edited residents', {
			rel_from: 'city',
			rel_to: ['person'],
			rel_name: 'edited-residents',
			from_args: {
				enable_ui: true,
				sortable: false,
				max_items: 100,
				labels: { name: 'Residents' },
			},
		});

		await page.goto(ADMIN_PAGE);
		await page
			.getByRole('row', { name: /Edited residents/ })
			.getByRole('button', { name: 'Edit', exact: true })
			.click();

		const panel = editorPanel(page);
		await panel.getByLabel('Label').fill('Renamed residents');
		await panel.getByLabel('Panel title').first().fill('City residents');
		await panel.getByRole('button', { name: 'Save' }).click();

		await expect(panel).toHaveCount(0);
		await expect(page.getByRole('row', { name: /Renamed residents/ })).toBeVisible();

		await admin.editPost(testData.posts.city[0]);
		await editor.openDocumentSettingsSidebar();
		await ccPage.expandRelationshipPanel('city_person_edited-residents');

		await expect(
			ccPage
				.getRelationshipPanel('city_person_edited-residents')
				.locator('.components-panel__body-title'),
		).toHaveText('City residents');
	});

	test('creates a post-to-user relationship', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);

		await page.goto(ADMIN_PAGE);
		await page.getByRole('button', { name: 'Add new Relationship' }).click();

		const panel = editorPanel(page);
		await panel.getByRole('radio', { name: 'Posts to users' }).check();
		await panel.getByLabel('Label').fill('City mayors');
		await panel.getByRole('combobox', { name: /^Post type/ }).selectOption({ label: 'Cities' });

		await expect(panel.getByRole('checkbox', { name: 'People' })).toHaveCount(0);

		await panel.getByRole('button', { name: 'Save' }).click();
		await expect(panel).toHaveCount(0);

		const row = page.getByRole('row', { name: /City mayors/ });
		await expect(row.getByRole('cell', { name: 'Posts to users', exact: true })).toBeVisible();
		await expect(row.getByRole('cell', { name: 'Users', exact: true })).toBeVisible();

		await admin.editPost(testData.posts.city[0]);
		await editor.openDocumentSettingsSidebar();
		await ccPage.expandRelationshipPanel('city_user_city-mayors');
		await expect(ccPage.getRelationshipManager('city_user_city-mayors')).toBeVisible({
			timeout: TIMEOUTS.PANEL_VISIBLE,
		});
	});

	test('shows a relationship whose post type is gone as inactive', async ({ page }) => {
		createCustomRelationship('Orphaned relationship', {
			rel_from: 'retired_type',
			rel_to: ['person'],
			rel_name: 'orphaned',
		});

		await page.goto(ADMIN_PAGE);

		const row = page.getByRole('row', { name: /Orphaned relationship/ });
		await expect(row.getByRole('cell', { name: 'Inactive', exact: true })).toBeVisible({
			timeout: TIMEOUTS.PANEL_VISIBLE,
		});
	});

	test('leaves out the editor panel on a side where it is turned off', async ({
		admin,
		editor,
		page,
		testData,
	}) => {
		const ccPage = new ContentConnectPage(page);

		createCustomRelationship('One-sided residents', {
			rel_from: 'city',
			rel_to: ['person'],
			rel_name: 'one-sided-residents',
			from_args: { enable_ui: false, sortable: false, max_items: 100 },
			to_args: { enable_ui: true, sortable: false, max_items: 100 },
		});

		await admin.editPost(testData.posts.person[0]);
		await editor.openDocumentSettingsSidebar();
		await ccPage.expandRelationshipPanel('city_person_one-sided-residents');
		await expect(ccPage.getRelationshipManager('city_person_one-sided-residents')).toBeVisible({
			timeout: TIMEOUTS.PANEL_VISIBLE,
		});

		await admin.editPost(testData.posts.city[0]);
		await editor.openDocumentSettingsSidebar();
		// Wait for the city's other panels so the missing one is not just still loading.
		await ccPage.expandRelationshipPanel('university_city_cities');
		await expect(ccPage.getRelationshipManager('city_person_one-sided-residents')).toHaveCount(
			0,
		);
	});

	test('stops offering items once the maximum is reached', async ({
		admin,
		editor,
		page,
		testData,
	}) => {
		const ccPage = new ContentConnectPage(page);
		const relKey = 'city_person_limited-residents';

		createCustomRelationship('Limited residents', {
			rel_from: 'city',
			rel_to: ['person'],
			rel_name: 'limited-residents',
			from_args: { enable_ui: true, sortable: false, max_items: 1 },
		});

		await admin.editPost(testData.posts.city[1]);
		await editor.openDocumentSettingsSidebar();
		await ccPage.expandRelationshipPanel(relKey);

		await ccPage.searchAndSelectItem(relKey, 'Person 1');

		await expect(ccPage.getSelectedItems(relKey)).toHaveCount(1);
		await expect(ccPage.getContentPickerSearchInput(relKey)).toHaveCount(0);
		// Reordering is off for this relationship.
		await expect(ccPage.getRelationshipManager(relKey).locator('.move-up-button')).toHaveCount(
			0,
		);
	});

	test('offers reordering when it is allowed', async ({ admin, editor, page, testData }) => {
		const ccPage = new ContentConnectPage(page);
		const relKey = 'city_person_ordered-residents';

		createCustomRelationship('Ordered residents', {
			rel_from: 'city',
			rel_to: ['person'],
			rel_name: 'ordered-residents',
			from_args: { enable_ui: true, sortable: true, max_items: 5 },
		});

		await admin.editPost(testData.posts.city[2]);
		await editor.openDocumentSettingsSidebar();
		await ccPage.expandRelationshipPanel(relKey);

		// The picker shows reorder controls once there are two items to order.
		await ccPage.searchAndSelectItem(relKey, 'Person 2');
		await ccPage.searchAndSelectItem(relKey, 'Person 3');
		await expect(ccPage.getSelectedItems(relKey)).toHaveCount(2);

		await expect(
			ccPage.getRelationshipManager(relKey).locator('.move-up-button'),
		).not.toHaveCount(0);
	});

	test('searches and filters the list', async ({ page }) => {
		createCustomRelationship('Searchable residents', {
			rel_from: 'city',
			rel_to: ['person'],
			rel_name: 'searchable-residents',
		});

		await page.goto(ADMIN_PAGE);
		await expect(page.getByRole('row', { name: /Searchable residents/ })).toBeVisible({
			timeout: TIMEOUTS.PANEL_VISIBLE,
		});

		const bodyRows = page.locator('.dataviews-view-table tbody tr');

		await page.getByRole('searchbox', { name: 'Search relationships' }).fill('Searchable');
		await expect(bodyRows).toHaveCount(1);
		await expect(bodyRows.first()).toContainText('Searchable residents');

		await page.getByRole('searchbox', { name: 'Search relationships' }).fill('');
		await expect(bodyRows).not.toHaveCount(1);

		await page.getByRole('button', { name: 'Add filter' }).click();
		await page
			.getByRole('menu', { name: 'Add filter' })
			.getByRole('menuitem', { name: 'Source' })
			.click();
		await page.getByRole('option', { name: 'Custom' }).click();
		await page.keyboard.press('Escape');

		await expect(bodyRows).toHaveCount(1);
		await expect(bodyRows.first()).toContainText('Searchable residents');
	});

	test('reports a relationship that no longer exists', async ({ page }) => {
		await page.goto(`${ADMIN_PAGE}&p=${encodeURIComponent('/?edit=999999')}`);

		await expect(page.getByText('This relationship no longer exists.')).toBeVisible({
			timeout: TIMEOUTS.PANEL_VISIBLE,
		});

		await page.getByRole('button', { name: 'Close', exact: true }).click();
		await expect(page.getByText('This relationship no longer exists.')).toHaveCount(0);
	});

	test('keeps Save disabled until the form is valid', async ({ page }) => {
		await page.goto(ADMIN_PAGE);
		await page.getByRole('button', { name: 'Add new Relationship' }).click();

		const panel = editorPanel(page);
		const save = panel.getByRole('button', { name: 'Save' });

		await expect(save).toBeDisabled();

		await panel.getByLabel('Label').fill('Valid residents');
		await panel.getByRole('combobox', { name: /^Post type/ }).selectOption({ label: 'Cities' });
		await expect(save).toBeDisabled();

		await panel.getByRole('checkbox', { name: 'People' }).check();
		await expect(save).toBeEnabled();

		await panel.getByLabel('Maximum items').first().fill('0');
		await expect(save).toBeDisabled();

		await panel.getByLabel('Maximum items').first().fill('5');
		await expect(save).toBeEnabled();
	});
});
