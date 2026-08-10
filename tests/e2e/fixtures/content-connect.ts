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
	 * Expands the panel containing the given relationship manager.
	 *
	 * Since WordPress no longer renders `data-name` on PluginDocumentSettingPanel,
	 * we expand all collapsed sidebar panels, then verify the target manager is visible.
	 */
	async expandRelationshipPanel(relKey: string): Promise<void> {
		const manager = this.getRelationshipManager(relKey);

		// Already expanded
		if (await manager.isVisible()) return;

		const sidebar = this.page.locator('.interface-interface-skeleton__sidebar');

		// Expand all collapsed panels at once for speed
		const closedToggles = sidebar.locator(
			'.components-panel__body:not(.is-opened) .components-panel__body-toggle'
		);
		const count = await closedToggles.count();
		for (let i = 0; i < count; i++) {
			// Always click the first closed toggle since previous ones become opened
			const toggle = sidebar.locator(
				'.components-panel__body:not(.is-opened) .components-panel__body-toggle'
			).first();
			if ((await toggle.count()) === 0) break;
			await toggle.click();
		}

		await manager.waitFor({ state: 'visible', timeout: TIMEOUTS.PANEL_VISIBLE });
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
		await this.getContentPickerResults(relKey).first()
			.waitFor({ state: 'visible', timeout: TIMEOUTS.SEARCH_RESULTS });
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
