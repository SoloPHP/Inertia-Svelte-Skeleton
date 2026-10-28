<?php

/**
 * The HTML document of a first visit, rendered by Solo\Inertia\PhpRootView.
 * After it, the client asks for pages as JSON and this file is not used again.
 *
 * @var string $head Elements from server-side rendering, '' without it
 * @var string $body The page object's script element and the root element the client mounts on
 * @var array<string, mixed> $page The page object
 * @var App\Http\Vite $vite
 * @var string $appName
 */

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($appName) ?></title>
    <?= $vite->tags('resources/js/app.js') ?>
    <?= $head ?>
</head>
<body>
    <?= $body ?>
</body>
</html>
