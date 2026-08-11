import { execSync } from 'child_process';
import * as path from 'path';

export const WP_CLI_PATH = path.join(__dirname, '../../..');

export function wpCli(command: string): string {
	try {
		const result = execSync(`npx wp-env run tests-cli wp ${command}`, {
			cwd: WP_CLI_PATH,
			encoding: 'utf-8',
		});
		return result;
	} catch (error) {
		console.error(`WP-CLI command failed: wp ${command}`);
		throw error;
	}
}

export function resetTestData(): void {
	wpCli('content-connect-example delete --yes');
	wpCli('content-connect-example generate');
}

/**
 * Post title prefixes matching Data_Generator::TITLE_PREFIX.
 * Titles are "<Prefix> <n>" with a 1-based index.
 */
const TITLE_PREFIX: Record<string, string> = {
	university: 'University',
	city: 'City',
	person: 'Person',
	course: 'Course',
	campus: 'Campus',
};

/**
 * User display names matching Data_Generator::USERS.
 */
const USER_NAMES = [
	'Alex Reed',
	'Blair Stone',
	'Casey Long',
	'Dana Frost',
	'Evan Pope',
	'Farah Quinn',
];

export function getPostTitle(postType: string, index: number): string {
	const prefix = TITLE_PREFIX[postType] ?? postType;
	return `${prefix} ${index}`;
}

export function getUserDisplayName(index: number): string {
	return USER_NAMES[index - 1] ?? `User ${index}`;
}
