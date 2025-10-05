<?php

declare(strict_types=1);

namespace BxJsonCli\BxJsonCli\Cli\Command\Parser;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetItemsCommand extends Command
{
    use \BxJsonCli\BxJsonCli\Cli\Command\Parser\Service\ParserService;

    protected function configure()
    {
        $config = $this->loadConfig();

        $defaultHost = $config['value']['redis']['host'] ?? 'localhost';
        $defaultPort = $config['value']['redis']['port'] ?? 6379;
        $defaultKey = $config['value']['default_keys']['items_key'] ?? 'items';

        $this
            ->setName('bxjsoncli:get-items')
            ->setDescription('Retrieve stored items from Redis as associative arrays, optionally in batches')
            ->addOption('redis-host', null, InputOption::VALUE_OPTIONAL, 'Redis host', $defaultHost)
            ->addOption('redis-port', null, InputOption::VALUE_OPTIONAL, 'Redis port', $defaultPort)
            ->addOption('redis-key', null, InputOption::VALUE_OPTIONAL, 'Redis key where items are stored', $defaultKey)
            ->addOption('start', null, InputOption::VALUE_OPTIONAL, 'Starting index for the range', 0)
            ->addOption('end', null, InputOption::VALUE_OPTIONAL, 'Ending index for the range (-1 for all)', -1);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $redisHost = $input->getOption('redis-host');
        $redisPort = (int)$input->getOption('redis-port');
        $redisKey = $input->getOption('redis-key');
        $start = (int)$input->getOption('start');
        $end = (int)$input->getOption('end');

        $this->initRedis($redisHost, $redisPort);

        $jsonItems = $this->redis->lrange($redisKey, $start, $end);
        $items = [];
        foreach ($jsonItems as $json) {
            $items[] = json_decode($json, true);
        }

        $output->writeln(json_encode($items, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
