<?php

namespace TaskService\Test\Unit\Services;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TaskService\Models\Email;
use TaskService\Models\Task;
use TaskService\Services\EmailService;
use TaskService\Services\ServicesMocks;
use TaskService\Views\TaskCompletedEmail;

class EmailServiceTest extends TestCase
{
    protected Task $task;

    public function setUp(): void
    {
        require_once __DIR__ . '/ServicesMocks.php';

        $this->task = new Task();
        $this->task->id = 41;
        $this->task->title = 'test';
        $this->task->last_updated_by = 'foo.receiver@invalid.local';
    }

    public function testSendEmail(): void
    {
        ServicesMocks::$mailReturn = true;

        $email = new TaskCompletedEmail();
        $email->subject = 'Task #41 completed';
        $email->from = 'foo.sender@invalid.local';
        $email->to = 'foo.receiver@invalid.local';
        $email->task = $this->task;

        $service = new EmailService();
        $service->sendEmail($email);

        $params = ServicesMocks::$mailParams;

        $this->assertStringContainsString($this->task->last_updated_by, $params[0]);
        $this->assertSame('=?UTF-8?Q?Task #41 completed?=', $params[1]);
        $this->assertStringContainsString('Task <b>test</b> completed!', $params[2]);
        $this->assertSame($email->from, $params[3]['From']);
    }

    public function testSendEmailMissingTemplate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing template');

        $email = new Email();
        $email->template = '';

        $service = new EmailService();
        $service->sendEmail($email);
    }

    public function testSendEmailMissingTemplateFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing template file');

        $email = new Email();
        $email->template = 'invalid';

        $service = new EmailService();
        $service->sendEmail($email);
    }

    public function testSendEmailFailure(): void
    {
        $this->expectWarning();
        $this->expectWarningMessage('failed to send');

        ServicesMocks::$mailReturn = false;

        $email = new TaskCompletedEmail();
        $email->task = $this->task;
        $email->to = 'bar@invalid.local';

        $service = new EmailService();
        $service->sendEmail($email);

        $this->assertTrue(true);
    }
}
