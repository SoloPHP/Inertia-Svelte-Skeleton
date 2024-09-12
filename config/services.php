<?php declare(strict_types=1);

use Solo\Database;
use Solo\Inertia;
use Solo\Settings;
use Psr\Container\ContainerInterface;

return function (ContainerInterface $container) {
    $container->set(Settings::class, function () {
        $settings = file_exists(ROOT_PATH.'/config/settings.local.php') ?
            ROOT_PATH . '/config/settings.local.php' :
            ROOT_PATH . '/config/settings.php';
        return new Settings(require $settings);
    });

    $container->set('assetsVersion', function () {
        return md5_file(ROOT_PATH . '/public/build/.vite/manifest.json');
    });

    $container->set(Inertia::class, function () {
        $manifestJsonPath = ROOT_PATH . '/public/build/.vite/manifest.json';
        $manifestData = json_decode(file_get_contents($manifestJsonPath), true);

        $rootTpl = ROOT_PATH . '/src/app.php';
        $assetsVersion = md5_file($manifestJsonPath);
        $js = '/build/' . $manifestData['src/app.js']['file'];
        $css = '/build/' . $manifestData['src/app.js']['css'][0];

        return new Inertia($rootTpl, $assetsVersion, $js, $css);
    });

    $container->set(Database::class, function (ContainerInterface $container) {
        $settings = $container->get(Settings::class);
        extract($settings->get('database'));

        return (new Database)
            ->setLogLocation(ROOT_PATH . '/logs')
            ->setLogErrors(false) //set true for production
            ->connect($hostname, $username, $password, $dbname);
    });
};