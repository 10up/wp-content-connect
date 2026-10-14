#!/usr/bin/env node
/**
 * Copy the version from package.json (where Changesets bumps it) into every
 * other place that carries it: the plugin header, the CONTENT_CONNECT_VERSION
 * constant, package-lock.json and, for stable releases only, the readme's
 * Stable tag. Stable releases also drop the prerelease sections from
 * CHANGELOG.md, since the release's own section repeats them.
 *
 * `--list` prints the files this touches instead, so the release workflow can
 * resolve them when trunk is merged back into develop.
 */
import { readFileSync, writeFileSync } from 'node:fs';

const root = new URL( '../', import.meta.url );
const read = ( path ) => readFileSync( new URL( path, root ), 'utf8' );

const { version } = JSON.parse( read( 'package.json' ) );
const isPrerelease = version.includes( '-' );

const replacements = {
	'content-connect.php': [ [ /^( \* Version:\s+).+$/m, `$1${ version }` ] ],
	'includes/Plugin.php': [
		[ /define\( 'CONTENT_CONNECT_VERSION', '[^']*' \);/, `define( 'CONTENT_CONNECT_VERSION', '${ version }' );` ],
	],
	'readme.txt': isPrerelease ? [] : [ [ /^(Stable tag:\s+).+$/m, `$1${ version }` ] ],
};

if ( process.argv.includes( '--list' ) ) {
	console.log( [ ...Object.keys( replacements ), 'package-lock.json', 'CHANGELOG.md' ].join( '\n' ) );
	process.exit( 0 );
}

for ( const [ path, patterns ] of Object.entries( replacements ) ) {
	let source = read( path );

	for ( const [ pattern, replacement ] of patterns ) {
		if ( ! pattern.test( source ) ) {
			console.error( `Could not find ${ pattern } in ${ path }.` );
			process.exit( 1 );
		}
		source = source.replace( pattern, replacement );
	}

	writeFileSync( new URL( path, root ), source );
}

const lock = JSON.parse( read( 'package-lock.json' ) );
lock.version = version;
lock.packages[ '' ].version = version;
writeFileSync( new URL( 'package-lock.json', root ), `${ JSON.stringify( lock, null, 2 ) }\n` );

if ( ! isPrerelease ) {
	const changelog = read( 'CHANGELOG.md' ).replace( /^## \d+\.\d+\.\d+-.*\n[\s\S]*?(?=^## |(?![\s\S]))/gm, '' );
	writeFileSync( new URL( 'CHANGELOG.md', root ), changelog );
}

console.log( `Plugin files are now at ${ version }` );
