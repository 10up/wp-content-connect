#!/usr/bin/env node
/**
 * Copy the version from package.json (where Changesets bumps it) into every
 * other place that carries it: the plugin header, the CONTENT_CONNECT_VERSION
 * constant, package-lock.json and, for stable releases only, the readme's
 * Stable tag.
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

console.log( `Plugin files are now at ${ version }` );
