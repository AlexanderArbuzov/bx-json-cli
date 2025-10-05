<?php
return [
    'console' => [
        'value' => [
            'commands' => [
                \BxJsonCli\BxJsonCli\Cli\Command\Parser\parsejsoncommand::class,
                \BxJsonCli\BxJsonCli\Cli\Command\Parser\getitemscommand::class,
                \BxJsonCli\BxJsonCli\Cli\Command\Parser\getitemsbykeycommand::class,
                \BxJsonCli\BxJsonCli\Cli\Command\Parser\getitemscountcommand::class,
            ],
        ],
        'readonly' => true,
    ],
    'parser' => [
        'value' => [
            // Redis connection settings (extracted to avoid repetition in services and commands)
            'redis' => [
                'scheme' => 'tcp',          // Protocol scheme (tcp or unix)
                'host' => 'localhost',      // Default host
                'port' => 6379,             // Default port
                'password' => null,         // Password if authentication is required (null for none)
                'timeout' => 5.0,           // Connection timeout in seconds
                'read_write_timeout' => -1, // Read/write timeout (-1 for unlimited)
            ],

            // Default Redis keys (used in parse-json and other commands)
            'default_keys' => [
                'items_key' => 'items',                // Key for storing parsed items
                'complete_key' => 'parsing_complete',  // Key for completion flag
            ],

            // JSON parsing defaults
            'json' => [
                'default_pointer' => '/items',  // Default JSON pointer for Items::fromStream
                'batch_size' => 1000,           // Max items to process in one batch (for memory optimization)
            ],

            // Guzzle HTTP client settings (for URL fetching in parse-json)
            'guzzle' => [
                'timeout' => 30.0,              // Request timeout in seconds
                'connect_timeout' => 5.0,       // Connection timeout
                'headers' => [                  // Default headers for requests
                    'User-Agent' => 'Bitrix-JsonParser/1.0',
                ],
            ],

            // Logging settings
            'logging' => [
                'level' => 'debug',             // Levels: debug, info, warning, error
                'file_path' => '/var/log/bitrix/jsonparser.log',  // Path to log file
                'enabled' => true,              // Enable/disable logging
            ],

            // General module settings
            'environment' => 'dev',             // 'dev' or 'prod' - affects verbosity, logging, etc.
            'max_batch_limit' => 5000,          // Max items to retrieve in get-items or similar commands (to prevent overload)
        ],
    ],
];
