<?php

namespace TaskService\Tests\Unit\Services;

use Exception;
use PHPUnit\Framework\TestCase;
use Redis;
use TaskService\Framework\App;
use TaskService\Services\RedisService;

class RedisServiceTest extends TestCase
{
    /** @var mixed */
    protected $app;

    public function setUp(): void
    {
        $map = ['getRedis' => $this->createMock(Redis::class)];

        $this->app = $this->createConfiguredMock(App::class, $map);
    }

    public function testAddMessageToStreamException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('redis error: add');

        $this->app->getRedis()->expects($this->once())
            ->method('xAdd')
            ->willReturn(false);

        $this->app->getRedis()->expects($this->once())
            ->method('getLastError')
            ->willReturn('add');

        $service = new RedisService($this->app);
        $service->addMessageToStream('test', ['foo' => 'bar']);
    }

    public function testremoveMessageFromStreamException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('redis error: remove');

        $this->app->getRedis()->expects($this->once())
            ->method('multi')
            ->willReturn($this->app->getRedis());

        $this->app->getRedis()->expects($this->once())
            ->method('xAck')
            ->willReturn($this->app->getRedis());

        $this->app->getRedis()->expects($this->once())
            ->method('xDel')
            ->willReturn($this->app->getRedis());

        $this->app->getRedis()->expects($this->once())
            ->method('exec')
            ->willReturn([false]);

        $this->app->getRedis()->expects($this->once())
            ->method('getLastError')
            ->willReturn('remove');

        $service = new RedisService($this->app);
        $service->removeMessageFromStream('test', 'group', '1234');
    }

    public function testGetPendingMessagesFromStreamException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('redis error: get_pending');

        $this->app->getRedis()->expects($this->once())
            ->method('xReadGroup')
            ->willReturn(false);

        $this->app->getRedis()->expects($this->once())
            ->method('getLastError')
            ->willReturn('get_pending');

        $service = new RedisService($this->app);
        $service->getMessagesFromStream('test', 'mygroup', 'consumer1', 10);
    }

    public function testGetNewMessagesFromStreamException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('redis error: get_new');

        $this->app->getRedis()->expects($this->exactly(2))
            ->method('xReadGroup')
            ->willReturnOnConsecutiveCalls([], false);

        $this->app->getRedis()->expects($this->once())
            ->method('getLastError')
            ->willReturn('get_new');

        $service = new RedisService($this->app);
        $service->getMessagesFromStream('test', 'mygroup', 'consumer1', 10);
    }

    public function testGetRetriesFromStreamException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('redis error: pending');

        $this->app->getRedis()->expects($this->once())
            ->method('xPending')
            ->willReturn(false);

        $this->app->getRedis()->expects($this->once())
            ->method('getLastError')
            ->willReturn('pending');

        $service = new RedisService($this->app);
        $service->getRetriesFromStream('test', 'group', 'consumer', 10);
    }
}
