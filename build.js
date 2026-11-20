/**
 * Minimal browserify build for the (legacy) Vue admin interface.
 *
 * node-sass leaves a libuv handle open after compiling the SCSS in the .vue
 * single-file components, so the `browserify` CLI writes the bundle but never
 * exits, hanging CI. Running the bundle through the API lets us force the
 * process to exit once the file is fully written.
 *
 * The Vue interface is slated to be replaced by Gutenberg panels, so this is a
 * deliberately throwaway shim rather than a modernised build.
 */
const fs = require( 'fs' );
const browserify = require( 'browserify' );
const vueify = require( 'vueify' );

const ENTRY = 'assets/js/src/content-connect.js';
const OUTPUT = 'assets/js/content-connect.js';

browserify( ENTRY )
	.transform( vueify )
	.bundle()
	.pipe( fs.createWriteStream( OUTPUT ) )
	.on( 'finish', () => {
		console.log( `Built ${ OUTPUT }` );
		process.exit( 0 );
	} )
	.on( 'error', ( err ) => {
		console.error( err );
		process.exit( 1 );
	} );
