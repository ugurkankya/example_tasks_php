<?php

namespace TaskService\Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use TaskService\Controllers\TasksController;
use TaskService\Exceptions\HttpException;
use TaskService\Framework\App;
use TaskService\Models\Customer;
use TaskService\Models\Task;
use TaskService\Repositories\TasksRepository;
use TaskService\Services\EmailService;
use TaskService\Views\TaskCompletedEmail;

class TasksControllerTest extends TestCase
{
    /** @var mixed */
    protected $app;

    protected Customer $customer;

    protected function setUp(): void
    {
        $map = [
            'getTasksRepository' => $this->createMock(TasksRepository::class),
            'getEmailService' => $this->createMock(EmailService::class),
        ];

        $this->customer = new Customer();
        $this->customer->id = 41;
        $this->customer->email = 'foo@invalid.local';

        $this->app = $this->createConfiguredMock(App::class, $map);
    }

    public function testGetCurrentTasks(): void
    {
        $task = new Task();
        $task->id = 42;

        $this->app->getTasksRepository()->expects($this->once())
            ->method('getCurrentTasks')
            ->with($this->customer)
            ->willReturn([$task]);

        $controller = new TasksController($this->app);

        $this->assertSame([$task], $controller->getCurrentTasks($this->customer));
    }

    public function testGetCompletedTasks(): void
    {
        $task = new Task();
        $task->id = 42;

        $this->app->getTasksRepository()->expects($this->once())
            ->method('getCompletedTasks')
            ->with($this->customer)
            ->willReturn([$task]);

        $controller = new TasksController($this->app);

        $this->assertSame([$task], $controller->getCompletedTasks($this->customer));
    }

    public function testGetTask(): void
    {
        $task = new Task();
        $task->id = 42;

        $this->app->getTasksRepository()->expects($this->once())
            ->method('taskExists')
            ->with($this->customer, 42)
            ->willReturn(true);

        $this->app->getTasksRepository()->expects($this->once())
            ->method('getTasks')
            ->with([42])
            ->willReturn([$task]);

        $controller = new TasksController($this->app);

        $this->assertSame($task, $controller->getTask($this->customer, 42));
    }

    public function testGetTaskNotFound(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('task not found');

        $this->app->getTasksRepository()->expects($this->once())
            ->method('taskExists')
            ->with($this->customer, 42)
            ->willReturn(true);

        $this->app->getTasksRepository()->expects($this->once())
            ->method('getTasks')
            ->with([42])
            ->willReturn([]);

        $controller = new TasksController($this->app);
        $controller->getTask($this->customer, 42);
    }

    public function testGetTaskNotFoundForCustomer(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('task not found');

        $this->app->getTasksRepository()->expects($this->once())
            ->method('taskExists')
            ->with($this->customer, 42)
            ->willReturn(false);

        $this->app->getTasksRepository()->expects($this->never())
            ->method('getTasks');

        $controller = new TasksController($this->app);
        $controller->getTask($this->customer, 42);
    }

    public function testDeleteTask(): void
    {
        $this->app->getTasksRepository()->expects($this->once())
            ->method('deleteTask')
            ->with(42);

        $this->app->getTasksRepository()->expects($this->once())
            ->method('taskExists')
            ->with($this->customer, 42)
            ->willReturn(true);

        $controller = new TasksController($this->app);
        $controller->deleteTask($this->customer, 42);
    }

    public function testDeleteTaskNotFound(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('task not found');

        $this->app->getTasksRepository()->expects($this->once())
            ->method('taskExists')
            ->with($this->customer, 42)
            ->willReturn(false);

        $this->app->getTasksRepository()->expects($this->never())
            ->method('deleteTask');

        $controller = new TasksController($this->app);
        $controller->deleteTask($this->customer, 42);
    }

    public function testCreateTask(): void
    {
        $task = new Task();
        $task->title = 'Test';
        $task->duedate = '2020-05-22';
        $task->completed = false;
        $task->last_updated_by = $this->customer->email;

        $this->app->getTasksRepository()->expects($this->once())
            ->method('createTask')
            ->with($this->customer, $task)
            ->willReturn(42);

        $task2 = clone $task;
        $task2->id = 42;

        $controller = new TasksController($this->app);

        $this->assertEquals($task2, $controller->createTask($this->customer, 'Test', '2020-05-22'));
    }

    public function testCreateTaskMissingTitle(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('missing title');

        $controller = new TasksController($this->app);
        $controller->createTask($this->customer, '', '2020-05-22');
    }

    public function testCreateTaskInvalidDuedate(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('invalid duedate');

        $controller = new TasksController($this->app);
        $controller->createTask($this->customer, 'test', 'tomorrow');
    }

    public function testUpdateTask(): void
    {
        $task = new Task();
        $task->id = 42;
        $task->title = 'test';
        $task->duedate = '2020-05-22';
        $task->completed = true;
        $task->last_updated_by = $this->customer->email;

        $email = new TaskCompletedEmail();
        $email->task = $task;

        $this->app->getTasksRepository()->expects($this->once())
            ->method('taskExists')
            ->with($this->customer, $task->id)
            ->willReturn(true);

        $this->app->getTasksRepository()->expects($this->once())
            ->method('updateTask')
            ->with($task);

        $controller = new TasksController($this->app);
        $controller->updateTask($this->customer, $task->id, $task->title, $task->duedate, $task->completed);
    }

    public function testUpdateTaskInvalidTitle(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('missing title');

        $this->app->getTasksRepository()->expects($this->never())
            ->method('updateTask');

        $controller = new TasksController($this->app);
        $controller->updateTask($this->customer, 42, '', '2020-05-22', false);
    }

    public function testUpdateTaskInvalidDuedate(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('invalid duedate');

        $this->app->getTasksRepository()->expects($this->never())
            ->method('updateTask');

        $controller = new TasksController($this->app);
        $controller->updateTask($this->customer, 42, 'test', '', false);
    }

    public function testUpdateTaskNotFound(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('task not found');

        $this->app->getTasksRepository()->expects($this->once())
            ->method('taskExists')
            ->with($this->customer, 42)
            ->willReturn(false);

        $this->app->getTasksRepository()->expects($this->never())
            ->method('updateTask');

        $controller = new TasksController($this->app);
        $controller->updateTask($this->customer, 42, 'test', '2020-05-22', false);
    }
}
