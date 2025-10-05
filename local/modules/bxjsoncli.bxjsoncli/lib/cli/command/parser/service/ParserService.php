<?php

declare(strict_types=1);

namespace BxJsonCli\BxJsonCli\Cli\Command\Parser\Service;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use Predis\Client as RedisClient;

trait ParserService
{
    protected $redis;
    protected $guzzle;
    private ?array $config = null;

    /**
     * Loads the configuration from .settings.php if not already loaded.
     *
     * @return array The loaded configuration array.
     * @throws \RuntimeException If the config file cannot be loaded or is invalid.
     */
    private function loadConfig(): array
    {
        if ($this->config === null) {
            $configPath = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/bxjsoncli.bxjsoncli/.settings.php';
            if (!file_exists($configPath)) {
                throw new \RuntimeException("Configuration file not found at {$configPath}");
            }

            $fullConfig = include $configPath;

            if (!is_array($fullConfig)) {
                throw new \RuntimeException('Invalid configuration: must return an array');
            }

            if (!isset($fullConfig['parser']) || !is_array($fullConfig['parser'])) {
                throw new \RuntimeException("Missing or invalid 'parser' section in configuration");
            }

            $this->config = $fullConfig['parser'];
        }

        return $this->config;
    }

    /**
     * Initializes the Redis client using provided params or defaults from config.
     *
     * @param string|null $redisHost Optional Redis host (falls back to config).
     * @param int|null $redisPort Optional Redis port (falls back to config).
     */
    protected function initRedis(?string $redisHost = null, ?int $redisPort = null): void
    {
        $config = $this->loadConfig()['value']['redis'] ?? [];

        $this->redis = new RedisClient([
            'scheme' => $config['scheme'] ?? 'tcp',
            'host' => $redisHost ?? $config['host'] ?? 'localhost',
            'port' => $redisPort ?? $config['port'] ?? 6379,
            'password' => $config['password'] ?? null,
            'timeout' => $config['timeout'] ?? 5.0,
            'read_write_timeout' => $config['read_write_timeout'] ?? -1,
        ]);
    }

    /**
     * Initializes the Guzzle HTTP client using defaults from config.
     */
    protected function initGuzzle(): void
    {
        $config = $this->loadConfig()['value']['guzzle'] ?? [];

        $this->guzzle = new Client([
            'timeout' => $config['timeout'] ?? 30.0,
            'connect_timeout' => $config['connect_timeout'] ?? 5.0,
            'headers' => $config['headers'] ?? ['User-Agent' => 'Bitrix-JsonParser/1.0'],
        ]);
    }

    /**
     * Returns the default Redis key for items from config.
     *
     * @return string The default items key.
     */
    protected function getDefaultItemsKey(): string
    {
        return $this->loadConfig()['value']['default_keys']['items_key'] ?? 'items';
    }

    /**
     * Returns the default Redis key for parsing completion flag from config.
     *
     * @return string The default completion key.
     */
    protected function getDefaultCompleteKey(): string
    {
        return $this->loadConfig()['value']['default_keys']['complete_key'] ?? 'parsing_complete';
    }

    /**
     * Returns the default JSON pointer from config.
     *
     * @return string The default JSON pointer.
     */
    protected function getDefaultJsonPointer(): string
    {
        return $this->loadConfig()['value']['json']['default_pointer'] ?? '/items';
    }

    /**
     * Returns the default JSON batch size from config.
     *
     * @return int The default batch size.
     */
    protected function getDefaultBatchSize(): int
    {
        return $this->loadConfig()['value']['json']['batch_size'] ?? 1000;
    }

    /**
     * Returns the max batch limit from config.
     *
     * @return int The max batch limit.
     */
    protected function getMaxBatchLimit(): int
    {
        return $this->loadConfig()['value']['max_batch_limit'] ?? 5000;
    }

    /**
     * Returns the logging configuration array.
     *
     * @return array The logging settings.
     */
    protected function getLoggingConfig(): array
    {
        return $this->loadConfig()['value']['logging'] ?? [];
    }

    /**
     * Returns the general environment setting from config.
     *
     * @return string The environment (dev or prod).
     */
    protected function getEnvironment(): string
    {
        return $this->loadConfig()['value']['environment'] ?? 'dev';
    }
}
