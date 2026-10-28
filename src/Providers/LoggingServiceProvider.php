<?php

declare(strict_types=1);

namespace App\Providers;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Solo\Application\Config;
use Solo\Contracts\Container\WritableContainerInterface;
use Solo\Dotenv\Dotenv;
use Solo\Logger\Logger;

/**
 * A PSR-3 logger writing to one rotated file. Services ask for LoggerInterface,
 * never for the Logger class.
 */
final class LoggingServiceProvider
{
    public function register(WritableContainerInterface $container): void
    {
        $container->set(LoggerInterface::class, function (WritableContainerInterface $c) {
            $file = Dotenv::string('LOG_FILE', 'storage/logs/app.log');

            $logger = new Logger(
                logFile: str_starts_with($file, '/') ? $file : $c->get(Config::class)->basePath . '/' . $file,
                maxFileSize: 5 * 1024 * 1024,
                maxFiles: 5,
            );
            $logger->setMinLevel(Dotenv::string('LOG_LEVEL', LogLevel::WARNING));

            return $logger;
        });
    }
}
