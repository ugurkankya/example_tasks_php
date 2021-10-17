<?php

use TaskService\Framework\App;

error_reporting(E_ALL);

if (php_sapi_name() != 'cli') {
    throw new Exception('invalid interface');
}

require __DIR__ . '/../vendor/autoload.php';

$app = new App([], [], $_SERVER, []);

if (!$app->getTasksRepository()->lockCron(basename(__FILE__))) {
    throw new Exception('lock cron failed');
}

// TODO optimize

$redis = $app->getRedis();
$stream = $app->getConfig()->redisStreamTasks;
$group = $app->getConfig()->redisStreamGroup;

// @see https://github.com/phpredis/phpredis#xGroup
$redis->xGroup('CREATE', $stream, $group, 0, true);

// > = new messages
// @see https://github.com/phpredis/phpredis#xReadGroup
$messages = $redis->xReadGroup($group, 'consumer1', [$stream => '>'], 10);
if ($messages === false) {
    throw new Exception('redis error: ' . ($redis->getLastError() ?? ''));
}

echo 'New messages:' . PHP_EOL;
foreach ($messages[$stream] ?? [] as $id => $data) {
    print_r([$id, $data]);

    // @see https://github.com/phpredis/phpredis#xAck
    // @see https://github.com/phpredis/phpredis#xDel
    $result = $redis->multi()
        ->xAck($stream, $group, [$id])
        ->xDel($stream, [$id])
        ->exec();

    if (in_array(false, $result, true)) {
        throw new Exception('redis error: ' . ($redis->getLastError() ?? ''));
    }
}

// 0 = pending messages
// @see https://github.com/phpredis/phpredis#xReadGroup
$messages = $redis->xReadGroup($group, 'consumer1', [$stream => 0], 10);
if ($messages === false) {
    throw new Exception('redis error: ' . ($redis->getLastError() ?? ''));
}

// @see https://github.com/phpredis/phpredis#xPending
$pendings = $redis->xPending($stream, $group, '-', '+', 10, 'consumer1');
if ($pendings === false) {
    throw new Exception('redis error: ' . ($redis->getLastError() ?? ''));
}

$retries = [];
foreach ($pendings as $pending) {
    // id => delivery count
    $retries[$pending[0]] = $pending[3];
}

echo 'Pending messages:' . PHP_EOL;
foreach ($messages[$stream] ?? [] as $id => $data) {
    if ($retries[$id] > 10) {
        error_log('retried too often: ' . json_encode([$id => $data]));
    } else {
        print_r([$id, $data, $retries[$id]]);
    }

    $result = $redis->multi()
        ->xAck($stream, $group, [$id])
        ->xDel($stream, [$id])
        ->exec();

    if (in_array(false, $result, true)) {
        throw new Exception('redis error: ' . ($redis->getLastError() ?? ''));
    }
}
