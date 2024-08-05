import {defineConfig} from 'vite';
import {svelte} from '@sveltejs/vite-plugin-svelte';
import sveltePreprocess from 'svelte-preprocess';

export default defineConfig({
    plugins: [
        svelte({
            preprocess: [sveltePreprocess()],
            onwarn: (warning, defaultHandler) => {
                if (warning.code.startsWith('a11y-')) return
                defaultHandler(warning)
            },
        })
    ],
    build: {
        outDir: '../public/build',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: './src/app.js',
        },
    },
    server: {
        strictPort: true,
        port: 5173
    }
});