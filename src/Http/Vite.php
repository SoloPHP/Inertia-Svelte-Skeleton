<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

/**
 * The tags that load a Vite entry in the root template.
 *
 * While `npm run dev` runs, vite.config.js writes the dev server's address to
 * public/hot, and the entry loads from there, with hot module replacement.
 * Otherwise it loads the files `npm run build` wrote to public/build, as their
 * manifest lists them: the entry, the CSS it imports and the chunks it needs first.
 */
final readonly class Vite
{
    // Where vite.config.js builds to, in public/
    private const string BUILD = 'build';

    public function __construct(private string $publicPath)
    {
    }

    /** The asset version: each build changes it, so a client that runs an older build reloads */
    public function version(): string
    {
        $manifest = $this->manifestFile();

        return is_file($manifest) ? (string) hash_file('xxh128', $manifest) : '';
    }

    /** The <link> and <script> tags of $entry, such as "resources/js/app.js" */
    public function tags(string $entry): string
    {
        $devServer = $this->devServer();
        if ($devServer !== null) {
            return self::script("$devServer/@vite/client") . self::script("$devServer/$entry");
        }

        $manifest = $this->manifest();
        if (!isset($manifest[$entry])) {
            throw new RuntimeException("Vite entry $entry is not in the manifest: run npm run build");
        }

        // The entry and the chunks it imports, directly or not: their CSS, and the chunks preloaded
        $chunks = [$entry];
        for ($i = 0; $i < count($chunks); $i++) {
            foreach ($manifest[$chunks[$i]]['imports'] ?? [] as $import) {
                if (isset($manifest[$import]) && !in_array($import, $chunks, true)) {
                    $chunks[] = $import;
                }
            }
        }

        $tags = '';
        foreach (array_unique(array_merge(...array_map(fn($c) => $manifest[$c]['css'] ?? [], $chunks))) as $css) {
            $tags .= '<link rel="stylesheet" href="' . self::escape(self::url($css)) . '">';
        }
        foreach (array_slice($chunks, 1) as $import) {
            $tags .= '<link rel="modulepreload" href="' . self::escape(self::url($manifest[$import]['file'])) . '">';
        }

        return $tags . self::script(self::url($manifest[$entry]['file']));
    }

    private function devServer(): ?string
    {
        $hot = $this->publicPath . '/hot';
        $url = is_file($hot) ? trim((string) file_get_contents($hot)) : '';

        return $url === '' ? null : rtrim($url, '/');
    }

    private function manifestFile(): string
    {
        return $this->publicPath . '/' . self::BUILD . '/.vite/manifest.json';
    }

    /** @return array<string, array{file: string, css?: list<string>, imports?: list<string>}> */
    private function manifest(): array
    {
        $file = $this->manifestFile();
        if (!is_file($file)) {
            throw new RuntimeException("Vite manifest not found at $file: run npm run build, or npm run dev");
        }

        /** @var array<string, array{file: string, css?: list<string>, imports?: list<string>}> */
        return json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    }

    private static function url(string $file): string
    {
        return '/' . self::BUILD . "/$file";
    }

    private static function script(string $src): string
    {
        return '<script type="module" src="' . self::escape($src) . '"></script>';
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES);
    }
}
