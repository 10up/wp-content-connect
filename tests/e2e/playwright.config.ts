import { defineConfig, devices } from '@playwright/test';
import * as path from 'path';

const baseURL = process.env.WP_BASE_URL ?? 'http://localhost:8913';
const storageStatePath = path.join(__dirname, '.auth/admin.json');

export default defineConfig({
	testDir: './specs',
	fullyParallel: false,
	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: 1,
	timeout: 60000,
	reporter: process.env.CI ? 'github' : 'list',
	globalSetup: require.resolve('./global-setup'),
	use: {
		baseURL,
		storageState: storageStatePath,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
	},
	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
		},
	],
	outputDir: './test-results',
});
