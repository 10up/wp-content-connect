import { test as base, expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page, Locator } from '@playwright/test';
import * as path from 'path';
import * as fs from 'fs';

export const TIMEOUTS = {
	PANEL_VISIBLE: 20_000,
	SEARCH_RESULTS: 5_000,
	DEFAULT_VISIBLE: 3_000,
} as const;

const testDataPath = path.join(__dirname, '../.auth/test-data.json');

export interface TestPostIds {
	university: number[];
	city: number[];
	person: number[];
	course: number[];
	campus: number[];
	department: number[];
}

export interface TestData {
	posts: TestPostIds;
	users: number[];
}

export interface ContentConnectFixtures {
	testData: TestData;
}

export const test = base.extend<ContentConnectFixtures>({
	testData: async ({}, use) => {
		if (!fs.existsSync(testDataPath)) {
			throw new Error(
				'Test data file not found. Run global-setup first to generate test data.'
			);
		}
		const raw = JSON.parse(fs.readFileSync(testDataPath, 'utf-8'));
		const data: TestData = {
			posts: raw.posts,
			users: raw.users,
		};
		await use(data);
	},
});

export class ContentConnectPage {
	readonly page: Page;

	constructor(page: Page) {
		this.page = page;
	}

	/**
	 * Returns the panel body that contains this relationship's manager.
	 * Only works after the panel has been expanded via expandRelationshipPanel().
	 */
	getRelationshipPanel(relKey: string): Locator {
		return this.page.locator(
			`.components-panel__body:has(.content-connect-relationship-manager-${relKey})`
		);
	}

	getRelationshipManager(relKey: string): Locator {
		return this.page.locator(`.content-connect-relationship-manager-${relKey}`);
	}

	/**
	 * Opens a post in the CLASSIC editor (wp-admin/post.php) and waits for the
	 * Content Connect relationship manager to mount inside its meta box.
	 */
	async gotoClassicEditor(postId: number, relKey: string): Promise<void> {
		await this.page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
		await this.getRelationshipManager(relKey).waitFor({
			state: 'visible',
			timeout: TIMEOUTS.PANEL_VISIBLE,
		});
	}

	/**
	 * Clicks the classic editor's Update/Publish button and waits for the save
	 * to round-trip (Content Connect intercepts the submit, persists, then the
	 * form submits and the page reloads back onto the edit screen).
	 */
	async saveClassicPost(): Promise<void> {
		await Promise.all([
			// The edit URL already matches "action=edit" before saving, so wait for
			// the post-save "message=" marker that only appears after the redirect.
			this.page.waitForURL( /post\.php\?post=\d+&action=edit&message=\d+/, {
				timeout: TIMEOUTS.PANEL_VISIBLE,
			} ),
			this.page.locator('#publish, #save-post').first().click(),
		]);
	}

	/**
	 * Expands the panel containing the given relationship manager.
	 *
	 * Since WordPress no longer renders `data-name` on PluginDocumentSettingPanel,
	 * we expand all collapsed sidebar panels, then verify the target manager is visible.
	 *
	 * The plugin's PluginDocumentSettingPanel fills mount asynchronously, after the
	 * relationships REST request resolves, and they mount collapsed. A single
	 * expand pass can therefore run before the panels exist and miss them entirely,
	 * so we poll: on each attempt, expand every currently-collapsed panel and check
	 * whether the target manager has become visible. This survives the async mount.
	 */
	async expandRelationshipPanel(relKey: string): Promise<void> {
		const manager = this.getRelationshipManager(relKey);

		// Already expanded
		if (await manager.isVisible()) return;

		const sidebar = this.page.locator('.interface-interface-skeleton__sidebar');
		const closedToggleSelector =
			'.components-panel__body:not(.is-opened) .components-panel__body-toggle';

		await expect
			.poll(
				async () => {
					// Expand every currently-collapsed panel. Re-query each time
					// since expanding one removes it from the collapsed set.
					const count = await sidebar.locator(closedToggleSelector).count();
					for (let i = 0; i < count; i++) {
						const toggle = sidebar.locator(closedToggleSelector).first();
						if ((await toggle.count()) === 0) break;
						await toggle.click();
					}

					return manager.isVisible();
				},
				{ timeout: TIMEOUTS.PANEL_VISIBLE }
			)
			.toBe(true);
	}

	getContentPicker(relKey: string): Locator {
		return this.getRelationshipManager(relKey).locator('.content-picker');
	}

	getContentPickerSearchInput(relKey: string): Locator {
		return this.getRelationshipManager(relKey).locator('input[type="search"]');
	}

	getContentPickerResults(relKey: string): Locator {
		return this.getRelationshipManager(relKey).locator('.tenup-content-search-list-item');
	}

	getSearchResultByText(relKey: string, text: string): Locator {
		return this.getContentPickerResults(relKey).filter({ hasText: text }).first();
	}

	getSelectedItems(relKey: string): Locator {
		return this.getRelationshipManager(relKey).locator('.block-editor-link-control__search-items tr');
	}

	async searchAndSelectItem(relKey: string, searchTerm: string): Promise<void> {
		const searchInput = this.getContentPickerSearchInput(relKey);
		await searchInput.fill(searchTerm);
		const firstResult = this.getContentPickerResults(relKey).first();
		await firstResult.waitFor({ state: 'visible', timeout: TIMEOUTS.SEARCH_RESULTS });
		await firstResult.click();
	}

	async searchAndWaitForResults(relKey: string, searchTerm: string): Promise<void> {
		const searchInput = this.getContentPickerSearchInput(relKey);
		await searchInput.fill(searchTerm);

		// The Content Picker debounces the query, fetches, and re-renders, so the
		// result list briefly empties between the initial (unfiltered) list and the
		// filtered results. Waiting for the first result can pass on that stale list
		// and then miss the specific match during the re-render window. Poll for the
		// result matching the search term instead, re-querying until it renders.
		await expect
			.poll(
				() => this.getSearchResultByText(relKey, searchTerm).isVisible(),
				{ timeout: TIMEOUTS.PANEL_VISIBLE }
			)
			.toBe(true);
	}

	async removeFirstSelectedItem(relKey: string): Promise<void> {
		const selectedItems = this.getSelectedItems(relKey);
		const countBefore = await selectedItems.count();
		await selectedItems.first().hover();
		const removeButton = selectedItems.first().locator('.remove-button');
		await removeButton.waitFor({ state: 'visible', timeout: TIMEOUTS.DEFAULT_VISIBLE });
		await removeButton.click();
		await expect(selectedItems).toHaveCount(countBefore - 1, { timeout: TIMEOUTS.SEARCH_RESULTS });
	}
}

export { expect };
