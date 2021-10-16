<?php

namespace TaskService\Tests\Integration\Infrastructure;

use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testEmailDelivery(): void
    {
        $subject = 'test ' . microtime(true);

        $result = mail('recipient@invalid.local', $subject, 'some content', 'From: sender@invalid.local');
        $this->assertTrue($result);

        $messages = json_decode(file_get_contents('http://mailhog:8025/api/v2/messages'), true);

        $this->assertEquals('sender@invalid.local', $messages['items'][0]['Content']['Headers']['From'][0] ?? '');
        $this->assertEquals('recipient@invalid.local', $messages['items'][0]['Content']['Headers']['To'][0] ?? '');
        $this->assertEquals('some content', $messages['items'][0]['Content']['Body'] ?? '');
        $this->assertEquals($subject, $messages['items'][0]['Content']['Headers']['Subject'][0] ?? '');
    }
}
