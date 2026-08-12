import { chromium, FullConfig } from '@playwright/test';
import { execSync } from 'child_process';
import * as path from 'path';
import * as fs from 'fs';

const storageStatePath = path.join(__dirname, '.auth/admin.json');
const testDataPath = path.join(__dirname, '.auth/test-data.json');
const pluginRoot = path.join(__dirname, '../..');

/**
 * Extracts JSON from WP-CLI output that may contain extra npm/wp-env logging.
 */
function extractJSON(output: string): any {
	const start = output.indexOf('{');
	const end = output.lastIndexOf('}');
	if (start === -1 || end === -1) {
		throw new Error('No JSON found in WP-CLI output');
	}
	return JSON.parse(output.substring(start, end + 1));
}

async function globalSetup(config: FullConfig): Promise<void> {
	const baseURL = config.projects[0].use.baseURL ?? 'http://localhost:8913';

	// Ensure auth directory exists
	const authDir = path.dirname(storageStatePath);
	if (!fs.existsSync(authDir)) {
		fs.mkdirSync(authDir, { recursive: true });
	}

	// Reset and seed test data via WP-CLI so every run starts from a known,
	// unmutated baseline (previous runs add/remove relationships in the DB).
	console.log('Seeding test data...');
	try {
		execSync('npx wp-env run tests-cli wp content-connect-example delete --yes', {
			cwd: pluginRoot,
			stdio: 'inherit',
		});
	} catch (error) {
		console.warn('Warning: Could not delete existing test data. Continuing.');
	}
	try {
		execSync('npx wp-env run tests-cli wp content-connect-example generate', {
			cwd: pluginRoot,
			stdio: 'inherit',
		});
	} catch (error) {
		console.warn('Warning: Could not seed test data via WP-CLI. Data may already exist.');
	}

	// Fetch test data IDs and save to file
	console.log('Fetching test data IDs...');
	try {
		const output = execSync('npx wp-env run tests-cli wp content-connect-example get_ids', {
			cwd: pluginRoot,
			encoding: 'utf-8',
		});
		const testData = extractJSON(output);
		fs.writeFileSync(testDataPath, JSON.stringify(testData, null, 2));
		console.log('Test data IDs saved.');
	} catch (error) {
		console.warn('Warning: Could not fetch test data IDs.');
	}

	// Create browser and authenticate
	const browser = await chromium.launch();
	const context = await browser.newContext();
	const page = await context.newPage();

	// Login to WordPress admin
	console.log('Logging in to WordPress admin...');
	await page.goto(`${baseURL}/wp-login.php`);

	await page.fill('#user_login', 'admin');
	await page.fill('#user_pass', 'password');
	await page.click('#wp-submit');

	// Wait for dashboard to load
	await page.waitForURL('**/wp-admin/**');
	console.log('Login successful!');

	// Save authentication state
	await context.storageState({ path: storageStatePath });

	await browser.close();
}

export default globalSetup;
export { storageStatePath, testDataPath };
