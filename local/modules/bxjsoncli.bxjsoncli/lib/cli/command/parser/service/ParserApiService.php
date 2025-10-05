<?php

declare(strict_types=1);

namespace BxJsonCli\BxJsonCli\Cli\Command\Parser\Service;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';

class ParserApiService
{
    use \BxJsonCli\BxJsonCli\Cli\Command\Parser\Service\ParserService;

    /**
     * @param string $redisHost Redis host (default: 'localhost')
     * @param int $redisPort Redis port (default: 6379)
     * @param string $redisKey Redis key where items are stored (default: 'items')
     * @param int $start Starting index for the range (default: 0)
     * @param int $end Ending index for the range (-1 for all, default: -1)
     * @return array Array of associative arrays (decoded items)
     */
    public function getItems(
        string $redisHost = null,
        int $redisPort = null,
        string $redisKey = null,
        int $start = 0,
        int $end = -1
    ): array {
        $this->initRedis($redisHost, $redisPort);

        $redisKey = $redisKey ?? $this->getDefaultItemsKey();

        $jsonItems = $this->redis->lrange($redisKey, $start, $end);
        $items = [];
        foreach ($jsonItems as $json) {
            $items[] = json_decode($json, true);
        }

        return $items;
    }

    /**
     * @param string $searchKey The key to search for (e.g., 'comment')
     * @param string $redisHost Redis host (default: 'localhost')
     * @param int $redisPort Redis port (default: 6379)
     * @param string $redisKey Redis list key (default: 'items')
     * @param int $start Start index for lrange (default: 0)
     * @param int $end End index for lrange (-1 for all, default: -1)
     * @return array Grouped results: [itemIndex => [value1, value2, ...], ...]
     */
    public function getItemsByKey(
        string $searchKey,
        string $redisHost = null,
        int $redisPort = null,
        string $redisKey = null,
        int $start = 0,
        int $end = -1
    ): array {
        $this->initRedis($redisHost, $redisPort);

        $redisKey = $redisKey ?? $this->getDefaultItemsKey();

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

        return $groupedResults;
    }

    /**
     * @param array $data Current data structure
     * @param string $searchKey Key to search for
     * @param array &$results Reference to result array for the current item
     */
    private function collectByKey(array $data, string $searchKey, array &$results): void {
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
