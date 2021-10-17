<?php

namespace TaskService\Services;

use Exception;
use TaskService\Framework\App;

class RedisService
{
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * @see https://github.com/phpredis/phpredis#xadd
     * @see https://redis.io/commands/XADD
     */
    public function addMessageToStream(string $stream, array $message): void
    {
        $redis = $this->app->getRedis();

        // * = auto generated id
        $result = $redis->xAdd($stream, '*', ['data' => json_encode($message)]);
        if (empty($result)) {
            throw new Exception('redis error: ' . ($redis->getLastError() ?? ''));
        }
    }

    /**
     * @see https://github.com/phpredis/phpredis#xAck
     * @see https://github.com/phpredis/phpredis#xDel
     */
    public function removeMessageFromStream(string $stream, string $group, string $messageId): void
    {
        $redis = $this->app->getRedis();

        $result = $redis->multi()
            ->xAck($stream, $group, [$messageId])
            ->xDel($stream, [$messageId])
            ->exec();

        if (in_array(false, $result, true)) {
            throw new Exception('redis error: ' . ($redis->getLastError() ?? ''));
        }
    }

    /**
     * @see https://github.com/phpredis/phpredis#xGroup
     * @see https://github.com/phpredis/phpredis#xReadGroup
     * @see https://github.com/phpredis/phpredis#xReadGroup
     */
    public function getMessagesFromStream(string $stream, string $group, string $consumer, int $count): array
    {
        $redis = $this->app->getRedis();

        $redis->xGroup('CREATE', $stream, $group, 0, true);

        // 0 = pending messages
        $pendingMessages = $redis->xReadGroup($group, $consumer, [$stream => 0], $count);
        if ($pendingMessages === false) {
            throw new Exception('redis error: ' . ($redis->getLastError() ?? ''));
        }

        // > = new messages
        $newMessages = $redis->xReadGroup($group, $consumer, [$stream => '>'], $count);
        if ($newMessages === false) {
            throw new Exception('redis error: ' . ($redis->getLastError() ?? ''));
        }

        return array_merge($newMessages[$stream] ?? [], $pendingMessages[$stream] ?? []);
    }

    /**
     * @see https://github.com/phpredis/phpredis#xPending
     */
    public function getRetriesFromStream(string $stream, string $group, string $consumer, int $count): array
    {
        $redis = $this->app->getRedis();

        $pendings = $redis->xPending($stream, $group, '-', '+', $count, $consumer);
        if ($pendings === false) {
            throw new Exception('redis error: ' . ($redis->getLastError() ?? ''));
        }

        $retries = [];
        foreach ($pendings as $pending) {
            // message id => delivery count
            $retries[$pending[0]] = $pending[3];
        }

        return $retries;
    }
}
