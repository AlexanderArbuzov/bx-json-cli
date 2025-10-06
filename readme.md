# Bitrix JSON CLI

Bitrix JSON CLI – парсер командной строки для потоковой загрузки больших данных в JSON-формате по URL или из локального файла в локальное хранилище и предоставления доступа через API для работы с ними как с массивом ассоциативных массивов.

## Установка для решения на BitrixVM

- Установите Composer
- Создайте `/home/bitrix/composer.json`
  ```json
  {
    "require": {
      "wikimedia/composer-merge-plugin": "^2.0"
    },
    "extra": {
      "merge-plugin": {
        "include": [
          "www/bitrix/composer-bx.json",
          "www/local/composer.json"
        ]
      }
    },
    "config": {
      "allow-plugins": {
        "wikimedia/composer-merge-plugin": true
      }
    }
  }
  ```
- Укажите в `/home/bitrix/www/bitrix/composer-bx.json` версию `symfony/console` `^6.0`
  ```json
  "require-dev": {
    "symfony/console": "^6.0"
  }
  ```
- Создайте `/home/bitrix/www/local/composer.json`
  ```json
  {
    "require": {
      "guzzlehttp/guzzle": "^7.10",
      "halaxa/json-machine": "^1.2",
      "predis/predis": "^3.2"
    },
    "autoload": {
      "psr-4": {
        "BxJsonCli\\BxJsonCli\\Cli\\Command\\Parser\\Service\\": "modules/bxjsoncli.bxjsoncli/lib/cli/command/parser/service/"
      }
    }
  }
  ```
- Укажите в `/home/bitrix/www/bitrix/.settings.php` config_path `"/home/bitrix/composer.json"`
  ```php
  'composer' => array (
    'value' => array (
      'config_path' => '/home/bitrix/composer.json'
    )
  ),
  ```
- Установите зависимости
- Загрузите файлы модуля в `/home/bitrix/www/local/modules/`
- Установите модуль

## CLI-команды

### bxjsoncli:parse-json

Команда парсит JSON из URL или локального файла в потоковом режиме, извлекает элементы с использованием JSON-указателя (опция `--json-pointer`, по умолчанию: `/items`) и хранит каждый элемент как JSON-строку в списке Redis. Очищает целевой ключ Redis перед парсингом, который вы можете задать с помощью опции `--redis-key` (по умолчанию `items`) и устанавливает флаг завершения по успешному окончанию (`complete-key`).

**Аргументы:**

- `url` (опционально): URL для получения JSON (например, `https://example.com/data.json`). Игнорируется, если указан `--local-file`.

**Опции:**

- `--local-file` (опционально): Путь к локальному JSON-файлу (имеет приоритет над URL).
- `--redis-host` (опционально): Хост Redis (по умолчанию: `localhost`).
- `--redis-port` (опционально): Порт Redis (по умолчанию: `6379`).
- `--redis-key` (опционально): Ключ списка Redis для хранения элементов (по умолчанию: `items`).
- `--complete-key` (опционально): Ключ Redis для флага завершения парсинга (по умолчанию: `parsing_complete`).
- `--json-pointer` (опционально): JSON-указатель на массив элементов (по умолчанию: `/items`; используйте `''` для массива на корневом уровне).

**Примеры:**

Парсинг из URL и хранение в ключе `my_items`:

```
php bitrix.php bxjsoncli:parse-json https://dummyjson.com/products --redis-key=my_items --json-pointer=''
```

Вывод: "Parsing completed. Items stored in Redis under key 'my_items' from https://dummyjson.com/products."

Парсинг из локального файла с пользовательскими настройками Redis:

```
php bitrix.php bxjsoncli:parse-json --local-file=/path/to/local.json --redis-host=redis.example.com --redis-port=6380 --redis-key=custom_items --json-pointer='/data/items'
```

### bxjsoncli:get-items

**Опции:**

- `--redis-host` (опционально): Хост Redis (по умолчанию: `localhost`).
- `--redis-port` (опционально): Порт Redis (по умолчанию: `6379`).
- `--redis-key` (опционально): Ключ списка Redis (по умолчанию: `items`).
- `--start` (опционально): Начальный индекс диапазона (по умолчанию: `0`).
- `--end` (опционально): Конечный индекс диапазона (`-1` для всех; по умолчанию: `-1`).

**Примеры:**

Получение всех элементов из ключа по умолчанию:

```
php bitrix.php bxjsoncli:get-items --redis-key=my_items
```

Получение элементов 10-19 с пользовательскими настройками Redis:

```
php bitrix.php bxjsoncli:get-items --redis-host=redis.example.com --redis-port=6380 --redis-key=my_items --start=10 --end=19
```

### bxjsoncli:get-items-by-key

**Аргументы:**

- `searchKey` (обязательно): Ключ для поиска (например, `comment`).

**Опции:**

- `--redis-host` (опционально): Хост Redis (по умолчанию: `localhost`).
- `--redis-port` (опционально): Порт Redis (по умолчанию: `6379`).
- `--redis-key` (опционально): Ключ списка Redis (по умолчанию: `items`).
- `--start` (опционально): Начальный индекс диапазона (по умолчанию: `0`).
- `--end` (опционально): Конечный индекс диапазона (`-1` для всех; по умолчанию: `-1`).

**Примеры:**

Извлечение значений `title` из всех элементов:

```
php bitrix.php bxjsoncli:get-items-by-key title --redis-key=my_items
```

Извлечение вложенного `comment` из элементов 0-9 с пользовательскими настройками Redis:

```
php bitrix.php bxjsoncli:get-items-by-key comment --redis-host=localhost --redis-port=6379 --redis-key=my_items --start=0 --end=9
```

### bxjsoncli:items-count

**Опции:**

- `--redis-host` (опционально): Хост Redis (по умолчанию: `localhost`).
- `--redis-port` (опционально): Порт Redis (по умолчанию: `6379`).
- `--redis-key` (опционально): Ключ списка Redis (по умолчанию: `items`).

**Примеры:**

Число элементов в ключе по умолчанию:

```
php bitrix.php bxjsoncli:items-count --redis-key=my_items
```

Вывод: "Count of items in 'my_items': 30"

Число элементов с пользовательскими настройками Redis:

```
php bitrix.php bxjsoncli:items-count --redis-host=localhost --redis-port=6379 --redis-key=custom_items
```

Вывод: "Count of items in 'custom_items': 100"

## API-методы

Класс `ParserApiService` предоставляет методы для доступа к хранимым данным на уровне кода.

### getItems

**Описание:** Извлекает элементы из Redis как массив ассоциативных массивов.

**Параметры:**

- `$redisHost` (string, опционально): Хост Redis (по умолчанию: `localhost`).
- `$redisPort` (int, опционально): Порт Redis (по умолчанию: `6379`).
- `$redisKey` (string, опционально): Ключ списка Redis (по умолчанию: `items`).
- `$start` (int, опционально): Начальный индекс (по умолчанию: `0`).
- `$end` (int, опционально): Конечный индекс (`-1` для всех; по умолчанию: `-1`).

**Возврат:** массив ассоциативных массивов (декодированные элементы).

**Пример:**

```php
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';

use BxJsonCli\BxJsonCli\Cli\Command\Parser\Service\ParserApiService;

$service = new ParserApiService();
$items = $service->getItems(null, null, 'my_items', 0, 9);

print_r($items);  // Выводит массив первых 10 элементов
```

### getItemsByKey

**Описание:** Извлекает значения по ключу из элементов в Redis, выполняя рекурсивный поиск. Группирует по индексу элемента.

**Параметры:**

- `$searchKey` (string, обязательно): Ключ для поиска.
- `$redisHost` (string, опционально): Хост Redis (по умолчанию: `localhost`).
- `$redisPort` (int, опционально): Порт Redis (по умолчанию: `6379`).
- `$redisKey` (string, опционально): Ключ списка Redis (по умолчанию: `items`).
- `$start` (int, опционально): Начальный индекс (по умолчанию: `0`).
- `$end` (int, опционально): Конечный индекс (`-1` для всех; по умолчанию: `-1`).

**Возврат:** массив `[itemIndex => [value1, value2, ...], ...]`

**Пример:**

```php
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';

use BxJsonCli\BxJsonCli\Cli\Command\Parser\Service\ParserApiService;

$service = new ParserApiService();
$results = $service->getItemsByKey('title', null, null, 'my_items', 0, -1);

print_r($results);  // Выводит сгруппированные заголовки по индексу элемента
```


---
