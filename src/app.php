<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Skeleton</title>

    <!-- Google fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">

    <?php if ($app['props']['environment'] === 'development'): ?>
    <script type="module" src="http://localhost:5173/@vite/client"></script>
    <script type="module" src="http://localhost:5173/src/app.js"></script>
    <?php else: ?>
    <link rel="stylesheet" type="text/css" href="<?= $app['css'] ?>">
    <script src="<?= $app['js'] ?>" defer></script>
    <?php endif; ?>

</head>
<body>
<div id="app" data-page='<?= $app['page'] ?>'></div>
</body>
</html>