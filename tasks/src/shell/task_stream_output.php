<?php

use TaskService\Framework\App;

error_reporting(E_ALL);

if (php_sapi_name() != 'cli') {
    throw new Exception('invalid interface');
}

require __DIR__ . '/../vendor/autoload.php';

$app = new App([], [], $_SERVER, []);

// lock is held until disconnect (automatically when process ends)
if (!$app->getTasksRepository()->lockCron(basename(__FILE__))) {
    throw new Exception('lock cron failed');
}

$service = $app->getRedisService();
$stream = $app->getConfig()->redisStreamTasks;
$group = $app->getConfig()->redisStreamGroup;

$consumer = 'consumer1';

echo 'Messages:' . PHP_EOL;

$messages = $service->getMessagesFromStream($stream, $group, $consumer, 10);

$retries = $service->getRetriesFromStream($stream, $group, $consumer, count($messages));

foreach ($messages as $messageId => $data) {
    $retries[$messageId] ??= 0;

    if ($retries[$messageId] > 10) {
        error_log('retried too often: ' . json_encode([$messageId => $data]));
    } else {
        print_r([$messageId, $data, $retries[$messageId]]);
    }

    $service->removeMessageFromStream($stream, $group, $messageId);
}
