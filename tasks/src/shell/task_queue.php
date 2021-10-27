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

$repo = $app->getTasksRepository();
$taskService = $app->getTaskProcessingService();
$redisService = $app->getRedisService();
$serializer = $app->getTasksSerializer();

$stream = $app->getConfig()->redisStreamTasks;

foreach ($repo->getTasksFromQueue() as $task) {
    $repo->updateTaskQueue($task->id);

    $taskService->processTaskUpdate($task);

    $redisService->addMessageToStream($stream, $serializer->serializeTask($task));

    $repo->deleteTaskQueue($task->id);
}
