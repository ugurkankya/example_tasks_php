<?php

namespace TaskService\Tests\Integration\Repositories;

use PHPUnit\Framework\TestCase;
use TaskService\Framework\App;
use TaskService\Services\RedisService;

class RedisServiceTest extends TestCase
{
    protected App $app;

    public function setUp(): void
    {
        $this->app = new App([], [], [], []);
    }

    public function testAddMessageToStream(): void
    {
        $stream = 'test_' . microtime(true);

        $redis = $this->app->getRedis();

        $service = new RedisService($this->app);
        $messageId = $service->addMessageToStream($stream, ['foo' => 'bar']);

        $actual = $redis->xRevRange($stream, '+', '-', 1);

        $this->assertEquals([$messageId => ['data' => json_encode(['foo' => 'bar'])]], $actual);

        $redis->del($stream);
    }

    public function testRemoveMessageFromStream(): void
    {
        $stream = 'test_' . microtime(true);

        $redis = $this->app->getRedis();

        $service = new RedisService($this->app);
        $messageId = $service->addMessageToStream($stream, ['foo' => 'bar']);

        $actual = $redis->xRevRange($stream, '+', '-', 1);

        $this->assertEquals([$messageId => ['data' => json_encode(['foo' => 'bar'])]], $actual);

        $service->removeMessageFromStream($stream, 'mygroup', $messageId);

        $actual = $redis->xRevRange($stream, '+', '-', 1);

        $this->assertSame([], $actual);

        $redis->del($stream);
    }
}
