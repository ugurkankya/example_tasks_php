<?php

namespace TaskService\Tests\Integration\Services;

use Exception;
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

        $messageId = $redis->xAdd($stream, '*', ['foo' => 'bar']);
        $actual = $redis->xRevRange($stream, '+', '-', 1);

        $this->assertEquals([$messageId => ['foo' => 'bar']], $actual);

        $service = new RedisService($this->app);
        $service->removeMessageFromStream($stream, 'mygroup', $messageId);

        $actual = $redis->xRevRange($stream, '+', '-', 1);

        $this->assertSame([], $actual);

        $redis->del($stream);
    }

    public function testGetMessagesFromStream(): void
    {
        $stream = 'test_' . microtime(true);

        $redis = $this->app->getRedis();

        $messageId = $redis->xAdd($stream, '*', ['foo' => 'bar']);
        $messageId2 = $redis->xAdd($stream, '*', ['foo2' => 'bar2']);

        $service = new RedisService($this->app);

        $actual = $service->getMessagesFromStream($stream, 'mygroup', 'consumer1', 10);
        $this->assertEquals([$messageId => ['foo' => 'bar'], $messageId2 => ['foo2' => 'bar2']], $actual);

        $service->removeMessageFromStream($stream, 'mygroup', $messageId);

        $actual = $service->getMessagesFromStream($stream, 'mygroup', 'consumer1', 10);
        $this->assertEquals([$messageId2 => ['foo2' => 'bar2']], $actual);

        $redis->del($stream);
    }

    public function testGetRetriesFromStream(): void
    {
        $stream = 'test_' . microtime(true);

        $redis = $this->app->getRedis();

        $messageId = $redis->xAdd($stream, '*', ['foo' => 'bar']);
        $messageId2 = $redis->xAdd($stream, '*', ['foo2' => 'bar2']);
        $messageId3 = $redis->xAdd($stream, '*', ['foo2' => 'bar2']);

        $service = new RedisService($this->app);

        $service->getMessagesFromStream($stream, 'mygroup', 'consumer1', 2);

        $actual = $service->getRetriesFromStream($stream, 'mygroup', 'consumer1', 3);

        $this->assertEquals([$messageId => 1, $messageId2 => 1], $actual);

        $service->removeMessageFromStream($stream, 'mygroup', $messageId);
        $service->getMessagesFromStream($stream, 'mygroup', 'consumer1', 2);

        $actual = $service->getRetriesFromStream($stream, 'mygroup', 'consumer1', 3);

        $this->assertEquals([$messageId2 => 2, $messageId3 => 1], $actual);

        $redis->del($stream);
    }

    public function testGetRetriesFromStreamException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('redis error: NOGROUP');

        $service = new RedisService($this->app);
        $service->getRetriesFromStream('test_' . microtime(true), 'mygroup', 'consumer1', 1);
    }
}
