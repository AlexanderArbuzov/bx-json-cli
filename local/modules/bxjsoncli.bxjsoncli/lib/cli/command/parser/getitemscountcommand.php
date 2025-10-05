<?php

declare(strict_types=1);

namespace BxJsonCli\BxJsonCli\Cli\Command\Parser;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetItemsCountCommand extends Command
{
    use \BxJsonCli\BxJsonCli\Cli\Command\Parser\Service\ParserService;

    protected function configure()
    {
        $config = $this->loadConfig();

        $defaultHost = $config['value']['redis']['host'] ?? 'localhost';
        $defaultPort = $config['value']['redis']['port'] ?? 6379;
        $defaultKey = $config['value']['default_keys']['items_key'] ?? 'items';

        $this
            ->setName('bxjsoncli:items-count')
            ->setDescription('Get the count of stored items in Redis')
            ->addOption('redis-host', null, InputOption::VALUE_OPTIONAL, 'Redis host', $defaultHost)
            ->addOption('redis-port', null, InputOption::VALUE_OPTIONAL, 'Redis port', $defaultPort)
            ->addOption('redis-key', null, InputOption::VALUE_OPTIONAL, 'Redis key where items are stored', $defaultKey);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $redisHost = $input->getOption('redis-host');
        $redisPort = (int)$input->getOption('redis-port');
        $redisKey = $input->getOption('redis-key');

        $this->initRedis($redisHost, $redisPort);

        $count = $this->redis->llen($redisKey);
        $output->writeln("Count of items in '{$redisKey}': {$count}");

        return self::SUCCESS;
    }
}
