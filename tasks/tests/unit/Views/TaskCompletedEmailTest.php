<?php

namespace TaskService\Test\Unit\Services;

use PHPUnit\Framework\TestCase;
use TaskService\Models\Task;
use TaskService\Services\EmailService;
use TaskService\Views\TaskCompletedEmail;

class TaskCompletedEmailTest extends TestCase
{
    public function testRenderView(): void
    {
        $task = new Task();
        $task->id = 42;
        $task->title = 'example title';
        $task->last_updated_by = 'foo@invalid.local';

        $email = new TaskCompletedEmail();
        $email->task = $task;
        $email->subject = sprintf($email->subject, $task->id);
        $email->to = $task->last_updated_by;

        $service = new EmailService();
        $output = $service->renderTemplate($email);

        $this->assertMatchesRegularExpression('!^<\!DOCTYPE html>\s+<html>\s*<body>.+?</body>\s*</html>$!s', $output);
        $this->assertStringContainsString('Task <b>example title</b> completed!', $output);
    }
}
