import { existsSync, rmSync, writeFileSync } from 'node:fs'
import inertia from '@inertiajs/vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'
import { defineConfig } from 'vite'

const devServer = 'http://localhost:5173'

/**
 * While `npm run dev` runs, public/hot holds the dev server's address: the root
 * template (src/Http/Vite.php) then loads the scripts from it instead of the build.
 */
function hotFile(file = 'public/hot') {
    const remove = () => existsSync(file) && rmSync(file)

    return {
        name: 'solo-hot-file',
        apply: 'serve',
        configureServer(server) {
            server.httpServer?.once('listening', () => writeFileSync(file, devServer))
            process.on('exit', remove)
            for (const signal of ['SIGINT', 'SIGTERM', 'SIGHUP']) {
                process.on(signal, () => process.exit())
            }
        },
    }
}

export default defineConfig(({ command }) => ({
    // Built files are served from public/build; the dev server serves the sources at its root
    base: command === 'build' ? '/build/' : '/',
    // public/ is the web root PHP serves: Vite has no static files of its own to copy there
    publicDir: false,
    plugins: [
        // Finds pages in resources/js/Pages, next to the entry, and loads each one when it is first visited
        inertia(),
        svelte(),
        hotFile(),
    ],
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: 'resources/js/app.js',
        },
    },
    server: {
        port: 5173,
        strictPort: true,
        // Asset URLs point at the dev server, as the page itself comes from PHP
        origin: devServer,
    },
}))
