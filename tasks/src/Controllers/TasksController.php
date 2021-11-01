<?php

namespace TaskService\Controllers;

use DateTime;
use TaskService\Exceptions\HttpException;
use TaskService\Framework\App;
use TaskService\Models\Customer;
use TaskService\Models\Task;

class TasksController
{
    protected App $app;

    /**
     * passing App allows late initialization (e.g. open db connection only when needed), avoids circular references
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function createTask(Customer $customer, string $title, string $duedate): Task
    {
        if (empty($title)) {
            throw new HttpException('missing title', 400);
        }
        if (!DateTime::createFromFormat('Y-m-d', $duedate)) {
            throw new HttpException('invalid duedate', 400);
        }

        $task = new Task();
        $task->title = $title;
        $task->duedate = $duedate;
        $task->completed = false;
        $task->last_updated_by = $customer->email;

        $repo = $this->app->getTasksRepository();

        $task->id = $repo->createTask($customer, $task);

        return $task;
    }

    public function updateTask(Customer $customer, int $taskId, string $title, string $duedate, bool $completed): Task
    {
        if (empty($title)) {
            throw new HttpException('missing title', 400);
        }
        if (!DateTime::createFromFormat('Y-m-d', $duedate)) {
            throw new HttpException('invalid duedate', 400);
        }

        $task = new Task();
        $task->id = $taskId;
        $task->title = $title;
        $task->duedate = $duedate;
        $task->completed = $completed;
        $task->last_updated_by = $customer->email;

        $repo = $this->app->getTasksRepository();

        if (!$repo->taskExists($customer, $task->id)) {
            throw new HttpException('task not found', 404);
        }

        $repo->updateTask($task);

        return $task;
    }

    public function deleteTask(Customer $customer, int $taskId): void
    {
        $repo = $this->app->getTasksRepository();

        if (!$repo->taskExists($customer, $taskId)) {
            throw new HttpException('task not found', 404);
        }

        $repo->deleteTask($taskId);
    }

    /**
     * @return Task[]
     */
    public function getCurrentTasks(Customer $customer): array
    {
        $repo = $this->app->getTasksRepository();

        return $repo->getCurrentTasks($customer);
    }

    /**
     * @return Task[]
     */
    public function getCompletedTasks(Customer $customer): array
    {
        $repo = $this->app->getTasksRepository();

        return $repo->getCompletedTasks($customer);
    }

    public function getTask(Customer $customer, int $taskId): Task
    {
        $repo = $this->app->getTasksRepository();

        if (!$repo->taskExists($customer, $taskId)) {
            throw new HttpException('task not found', 404);
        }

        $tasks = $repo->getTasks([$taskId]);
        if (empty($tasks)) {
            throw new HttpException('task not found', 404);
        }

        return $tasks[0];
    }
}
