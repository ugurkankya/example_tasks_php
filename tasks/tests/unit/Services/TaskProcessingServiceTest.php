<?php

namespace TaskService\Test\Unit\Services;

use PHPUnit\Framework\TestCase;
use TaskService\Framework\App;
use TaskService\Models\Task;
use TaskService\Services\EmailService;
use TaskService\Services\TaskProcessingService;
use TaskService\Views\TaskCompletedEmail;

class TaskProcessingServiceTest extends TestCase
{
    /** @var mixed */
    protected $app;

    protected Task $task;

    public function setUp(): void
    {
        $map = ['getEmailService' => $this->createMock(EmailService::class)];

        $this->app = $this->createConfiguredMock(App::class, $map);

        $task = new Task();
        $task->id = 42;
        $task->title = 'test';
        $task->duedate = '2020-05-22';
        $task->completed = false;
        $task->last_updated_by = 'foo@invalid.local';

        $this->task = $task;
    }

    public function testProcessTaskUpdateCompleted(): void
    {
        $this->task->completed = true;

        $email = new TaskCompletedEmail();
        $email->task = $this->task;
        $email->subject = sprintf($email->subject, $this->task->id);
        $email->to = $this->task->last_updated_by;

        $this->app->getEmailService()->expects($this->once())
            ->method('sendEmail')
            ->with($email);

        $service = new TaskProcessingService($this->app);
        $service->processTaskUpdate($this->task);
    }

    public function testProcessTaskUpdateUnCompleted(): void
    {
        $this->app->getEmailService()->expects($this->never())
            ->method('sendEmail');

        $service = new TaskProcessingService($this->app);
        $service->processTaskUpdate($this->task);
    }
}
