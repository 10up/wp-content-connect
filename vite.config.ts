import { defineConfig } from 'vite';
import { wp } from '@10up/wp-vite-plugins';

export default defineConfig(({ mode }) => {
	const isModulePass = mode === 'modules';

	return {
		plugins: [
			wp({
				buildType: isModulePass ? 'module' : 'script',
				adminPagesDir: './assets/js/admin-pages',
				devServer: false,
			}),
		],
		build: {
			outDir: 'dist',
			// The script pass cleans dist/, the module pass adds the boot admin pages to it.
			emptyOutDir: !isModulePass,
			rollupOptions: {
				// Bundled dependencies carry React Server Components directives, which mean nothing here.
				onwarn(warning, warn) {
					if (warning.code === 'MODULE_LEVEL_DIRECTIVE') {
						return;
					}

					warn(warning);
				},
				input: isModulePass
					? {}
					: {
							'js/block-editor': './assets/js/index.ts',
							'js/classic-editor': './assets/js/classic-editor.tsx',
							'js/admin-relationships': './assets/js/admin/index.tsx',
							'css/admin-styles': './assets/css/admin-styles.css',
							'css/admin-relationships': './assets/css/admin-relationships.css',
						},
				output: {
					entryFileNames: '[name].js',
					assetFileNames: '[name][extname]',
				},
			},
		},
	};
});
