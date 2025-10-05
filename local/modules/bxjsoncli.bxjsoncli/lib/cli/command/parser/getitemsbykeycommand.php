<?php

declare(strict_types=1);

namespace BxJsonCli\BxJsonCli\Cli\Command\Parser;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetItemsByKeyCommand extends Command
{
    use \BxJsonCli\BxJsonCli\Cli\Command\Parser\Service\ParserService;

    protected function configure()
    {
        $config = $this->loadConfig();

        $defaultHost = $config['value']['redis']['host'] ?? 'localhost';
        $defaultPort = $config['value']['redis']['port'] ?? 6379;
        $defaultKey = $config['value']['default_keys']['items_key'] ?? 'items';

        $this
            ->setName('bxjsoncli:get-items-by-key')
            ->setDescription('Extracts all values by a given key from JSON items in Redis, at any depth. Recursively traverses nested arrays/objects and groups results by item index.')
            ->addArgument('searchKey', InputArgument::REQUIRED, 'The key to search for (e.g., "foobar")')
            ->addOption('redis-host', null, InputOption::VALUE_OPTIONAL, 'Redis host', $defaultHost)
            ->addOption('redis-port', null, InputOption::VALUE_OPTIONAL, 'Redis port', $defaultPort)
            ->addOption('redis-key', null, InputOption::VALUE_OPTIONAL, 'Redis list key', $defaultKey)
            ->addOption('start', null, InputOption::VALUE_OPTIONAL, 'Start index for lrange', 0)
            ->addOption('end', null, InputOption::VALUE_OPTIONAL, 'End index for lrange (-1 for all)', -1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $searchKey = $input->getArgument('searchKey');
        $redisHost = $input->getOption('redis-host');
        $redisPort = (int) $input->getOption('redis-port');
        $redisKey = $input->getOption('redis-key');
        $start = (int) $input->getOption('start');
        $end = (int) $input->getOption('end');

        $this->initRedis($redisHost, $redisPort);

        $jsonItems = $this->redis->lrange($redisKey, $start, $end);
        $groupedResults = [];

        foreach ($jsonItems as $index => $json) {
            $data = json_decode($json, true);
            if (!is_array($data)) {
                $groupedResults[$index] = []; // Skip invalid JSON, add empty array
                continue;
            }

            $itemResults = [];
            $this->collectByKey($data, $searchKey, $itemResults);
            $groupedResults[$index] = $itemResults;
        }

        $output->writeln(json_encode($groupedResults, JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }

    /**
     * @param array $data Current data structure
     * @param string $searchKey Key to search for
     * @param array &$results Reference to result array for the current item
     */
    private function collectByKey(array $data, string $searchKey, array &$results): void
    {
        foreach ($data as $key => $value) {
            if ($key === $searchKey) {
                $results[] = $value; // Found the key, add its value
            }

            if (is_array($value)) {
                $this->collectByKey($value, $searchKey, $results); // Recurse into nested array
            }
        }
    }
}
