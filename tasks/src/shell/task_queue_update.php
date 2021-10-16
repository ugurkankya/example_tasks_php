<?php

use TaskService\Framework\App;
use TaskService\Views\TaskCompletedEmail;

error_reporting(E_ALL);

if (php_sapi_name() != 'cli') {
    throw new Exception('invalid interface');
}

require __DIR__ . '/../vendor/autoload.php';

$app = new App([], [], $_SERVER, []);

if (!$app->getTasksRepository()->lockCron(basename(__FILE__))) {
    throw new Exception('lock cron failed');
}

$repo = $app->getTasksRepository();

foreach ($repo->getTasksFromQueueUpdate() as $task) {
    $repo->updateTaskQueueUpdate($task->id);

    // TODO optimize
    if ($task->completed) {
        $email = new TaskCompletedEmail();
        $email->task = $task;
        $email->subject = sprintf($email->subject, $email->task->id);
        $email->to = $task->last_updated_by;

        $app->getEmailService()->sendEmail($email);
    }

    // TODO implement stream processor

    // TODO optimize
    $redis = $app->getRedis();
    $stream = $app->getConfig()->redisStreamTasks;
    $serializer = $app->getTasksSerializer();

    // * = auto generated id
    // @see https://github.com/phpredis/phpredis#xadd
    // @see https://redis.io/commands/XADD
    $result = $redis->xAdd($stream, '*', $serializer->serializeTask($task));
    if (empty($result)) {
        throw new Exception('redis error: ' . (string) $redis->getLastError());
    }

    $repo->deleteTaskQueueUpdate($task->id);
}