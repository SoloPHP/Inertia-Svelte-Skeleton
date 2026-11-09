import { rmSync, writeFileSync } from 'node:fs'
import inertia from '@inertiajs/vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'
import { defineConfig } from 'vite'

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
    },
}))

/**
 * Holds the dev server's address in public/hot while it runs, for Solo\Vite\Vite. Without
 * server.origin, the address is http://localhost and the port the server is given, `vite --port`
 * included, and it becomes the origin, so that URLs in the CSS point at the dev server too.
 */
function hotFile(file = 'public/hot') {
    let origin

    return {
        name: 'hot-file',
        apply: 'serve',
        // Runs once Vite has merged the command line into the config
        config(config) {
            origin = config.server?.origin ?? `http://localhost:${config.server?.port ?? 5173}`

            // A port another process holds must fail rather than move: the origin names this one
            return { server: { origin, strictPort: true } }
        },
        configureServer(server) {
            server.httpServer?.once('listening', () => {
                writeFileSync(file, origin)

                // Only a server that wrote the file removes it, so one that fails to start leaves
                // the running one's alone. Vite restarts the server with the config loaded anew:
                // one set of handlers per process is enough.
                const bound = Symbol.for('hot-file')
                if (process[bound]) {
                    return
                }
                process[bound] = true
                process.on('exit', () => rmSync(file, { force: true }))
                for (const signal of ['SIGINT', 'SIGTERM', 'SIGHUP']) {
                    process.on(signal, () => process.exit())
                }
            })
        },
    }
}
