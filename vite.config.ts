import { defineConfig } from 'vite';
import { wp } from '@10up/wp-vite-plugins';

export default defineConfig({
	plugins: [
		wp({
			devServer: false,
		}),
	],
	build: {
		outDir: 'dist',
		rollupOptions: {
			// Bundled dependencies carry React Server Components directives, which mean nothing here.
			onwarn(warning, warn) {
				if (warning.code === 'MODULE_LEVEL_DIRECTIVE') {
					return;
				}

				warn(warning);
			},
			input: {
				'js/block-editor': './assets/js/index.ts',
				'js/classic-editor': './assets/js/classic-editor.tsx',
				'css/admin-styles': './assets/css/admin-styles.css',
			},
			output: {
				entryFileNames: '[name].js',
				assetFileNames: '[name][extname]',
			},
		},
	},
});
