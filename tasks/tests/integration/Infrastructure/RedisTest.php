<?php

namespace TaskService\Tests\Integration\Infrastructure;

use PHPUnit\Framework\TestCase;
use TaskService\Framework\App;

class RedisTest extends TestCase
{
    public function testRedisConnection(): void
    {
        $app = new App([], [], [], []);

        $info = $app->getRedis()->info('memory');

        $this->assertTrue($info['used_memory'] / $info['maxmemory'] < 0.5);
    }
}
