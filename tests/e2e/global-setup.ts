import { request } from '@playwright/test';
import type { FullConfig } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';
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
	const baseURL = config.projects[0].use.baseURL ?? 'http://localhost:8889';

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

	// Authenticate with WordPress via RequestUtils (REST/cookie based) and persist
	// the storage state the `admin`/`editor` fixtures load. This is more reliable
	// than driving the wp-login.php form in a browser, whose submit can fail to
	// navigate on a fresh context.
	console.log('Authenticating with WordPress...');
	const requestContext = await request.newContext({ baseURL });
	const requestUtils = new RequestUtils(requestContext, { storageStatePath });
	await requestUtils.setupRest();
	await requestContext.dispose();
	console.log('Login successful!');
}

export default globalSetup;
export { storageStatePath, testDataPath };
