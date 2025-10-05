<?php

declare(strict_types=1);

namespace BxJsonCli\BxJsonCli\Cli\Command\Parser;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';

use JsonMachine\Items;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseJsonCommand extends Command
{
    use \BxJsonCli\BxJsonCli\Cli\Command\Parser\Service\ParserService;

    protected function configure()
    {
        $config = $this->loadConfig();

        $defaultHost = $config['value']['redis']['host'] ?? 'localhost';
        $defaultPort = $config['value']['redis']['port'] ?? 6379;
        $defaultKey = $config['value']['default_keys']['items_key'] ?? 'items';
        $completeKey = $config['value']['default_keys']['parsing_complete'] ?? 'parsing_complete';

        $this
            ->setName('bxjsoncli:parse-json')
            ->setDescription('Parse JSON from a URL or local file in a streaming manner and store items in Redis')
            ->addArgument('url', InputArgument::OPTIONAL, 'URL to fetch JSON from (ignored if local-file is provided)')
            ->addOption('local-file', null, InputOption::VALUE_OPTIONAL, 'Path to local JSON file (takes precedence over URL)')
            ->addOption('redis-host', null, InputOption::VALUE_OPTIONAL, 'Redis host', $defaultHost)
            ->addOption('redis-port', null, InputOption::VALUE_OPTIONAL, 'Redis port', $defaultPort)
            ->addOption('redis-key', null, InputOption::VALUE_OPTIONAL, 'Redis key to store the list of items', $defaultKey)
            ->addOption('complete-key', null, InputOption::VALUE_OPTIONAL, 'Redis key for completion flag', $completeKey)
            ->addOption('json-pointer', null, InputOption::VALUE_OPTIONAL, 'JSON pointer to the array of items', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $localFile = $input->getOption('local-file');
        $url = $input->getArgument('url');
        $redisHost = $input->getOption('redis-host');
        $redisPort = (int)$input->getOption('redis-port');
        $redisKey = $input->getOption('redis-key');
        $completeKey = $input->getOption('complete-key');
        $jsonPointer = $input->getOption('json-pointer');

        if (!$localFile && !$url) {
            $output->writeln('<error>Provide either --local-file or url argument.</error>');
            return self::FAILURE;
        }

        $this->initRedis($redisHost, $redisPort);
        $this->initGuzzle();

        $this->redis->del($redisKey);
        $this->redis->del($completeKey);

        $stream = null;
        if ($localFile) {
            if (!file_exists($localFile) || !is_readable($localFile)) {
                $output->writeln('<error>File not found or not readable: ' . $localFile . '</error>');
                return self::FAILURE;
            }
            $stream = fopen($localFile, 'r'); // Open local file as stream
            if (!$stream) {
                $output->writeln('<error>Failed to open local file stream.</error>');
                return self::FAILURE;
            }
        } elseif ($url) {
            $response = $this->guzzle->get($url, ['stream' => true]);
            $stream = $response->getBody()->detach();
        }

        $itemsIterator = Items::fromStream($stream, ['pointer' => $jsonPointer]);

        foreach ($itemsIterator as $item) {
            $this->redis->rpush($redisKey, json_encode($item));
        }

        $this->redis->set($completeKey, 'true');

        if (is_resource($stream)) {
            fclose($stream);
        }

        $output->writeln("Parsing completed. Items stored in Redis under key '{$redisKey}' from " . ($localFile ?: $url) . ".");

        return self::SUCCESS;
    }
}
