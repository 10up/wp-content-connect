import { test, expect, ContentConnectPage, TIMEOUTS } from '../fixtures/content-connect';
import type { Page } from '@playwright/test';
import { wpCli } from '../utils/helpers';

const ADMIN_PAGE = '/wp-admin/options-general.php?page=content-connect';

// The editor opens in the side panel as a form named after its heading.
const editorPanel = (page: Page) => page.getByRole('form', { name: /relationship$/ });

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
});
