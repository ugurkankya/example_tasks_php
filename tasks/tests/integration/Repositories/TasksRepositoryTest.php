<?php

namespace TaskService\Tests\Integration\Repositories;

use PDO;
use PHPUnit\Framework\TestCase;
use TaskService\Framework\App;
use TaskService\Models\Customer;
use TaskService\Models\Task;

class TasksRepositoryTest extends TestCase
{
    protected App $app;
    protected Customer $customer;
    protected Customer $customer2;
    protected Task $task;
    protected Task $task2;

    public function setUp(): void
    {
        $this->app = new App([], [], $_SERVER, []);

        $this->app->getDatabase()->beginTransaction();

        $this->customer = new Customer();
        $this->customer->id = 41;

        $this->customer2 = new Customer();
        $this->customer2->id = 42;

        $this->task = new Task();
        $this->task->title = 'test';
        $this->task->duedate = '2020-05-22';
        $this->task->completed = false;
        $this->task->last_updated_by = 'foo@invalid.local';

        $this->task2 = new Task();
        $this->task2->title = 'test2';
        $this->task2->duedate = '2020-05-23';
        $this->task2->completed = false;
        $this->task2->last_updated_by = 'foo@invalid.local';
    }

    public function tearDown(): void
    {
        $this->app->getDatabase()->rollBack();
    }

    public function testCreateTask(): void
    {
        $expected = $this->app->getTasksRepository()->createTask($this->customer, $this->task);
        $this->assertNotEmpty($expected);

        $query = '
            SELECT id, title, duedate, completed FROM task WHERE id = ? AND customer_id = ?
        ';
        $statement = $this->app->getDatabase()->prepare($query);
        $statement->execute([$expected, $this->customer->id]);

        $actual = $statement->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($actual);
        $this->assertEquals($expected, $actual['id']);
        $this->assertEquals('test', $actual['title']);
        $this->assertEquals('2020-05-22', $actual['duedate']);
        $this->assertEquals('0', $actual['completed']);
    }

    public function testTaskExists(): void
    {
        $repo = $this->app->getTasksRepository();

        $actual = $repo->createTask($this->customer, $this->task);

        $this->assertTrue($repo->taskExists($this->customer, $actual));
        $this->assertFalse($repo->taskExists($this->customer2, $actual));
        $this->assertFalse($repo->taskExists($this->customer, 42));
    }

    public function testGetCurrentTasks(): void
    {
        $repo = $this->app->getTasksRepository();

        $this->task->id = $repo->createTask($this->customer, $this->task);
        $this->task2->id = $repo->createTask($this->customer, $this->task2);

        $repo->createTask($this->customer2, $this->task);

        $actual = $repo->getCurrentTasks($this->customer);

        $this->assertCount(2, $actual);
        $this->assertEquals([$this->task, $this->task2], $actual);
    }

    public function testGetCompletedTasks(): void
    {
        $repo = $this->app->getTasksRepository();

        $this->task->id = $repo->createTask($this->customer, $this->task);
        $this->task->completed = true;

        $repo->updateTask($this->task);

        $this->assertEquals([$this->task], $repo->getCompletedTasks($this->customer));
    }

    public function testDeleteTask(): void
    {
        $repo = $this->app->getTasksRepository();

        $actual = $repo->createTask($this->customer, $this->task);

        $this->assertTrue($repo->taskExists($this->customer, $actual));

        $repo->deleteTask($actual);

        $this->assertFalse($repo->taskExists($this->customer, $actual));
    }

    public function testGetTasks(): void
    {
        $repo = $this->app->getTasksRepository();

        $this->task->id = $repo->createTask($this->customer, $this->task);

        $this->assertEquals([$this->task], $repo->getTasks([$this->task->id]));

        $this->assertEmpty($repo->getTasks([42]));
    }
}
