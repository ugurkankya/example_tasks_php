<?php

namespace TaskService\Repositories;

use PDO;
use TaskService\Framework\App;
use TaskService\Models\Customer;
use TaskService\Models\Task;

class TasksRepository
{
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function lockCron(string $identifier): bool
    {
        $statement = $this->app->getDatabase()->prepare('SELECT GET_LOCK(?, 1)');
        $statement->execute(['cron_' . $identifier]);

        return $statement->fetchColumn() === '1';
    }

    public function taskExists(Customer $customer, int $taskId): bool
    {
        $query = '
            SELECT id FROM task WHERE id = ? AND customer_id = ?
        ';
        $statement = $this->app->getDatabase()->prepare($query);
        $statement->execute([$taskId, $customer->id]);

        return !empty($statement->fetchColumn());
    }

    public function createTask(Customer $customer, Task $task): int
    {
        $db = $this->app->getDatabase();

        $inTransaction = $db->inTransaction();
        $inTransaction || $db->beginTransaction();

        $query = '
            INSERT INTO task SET customer_id = ?, title = ?, duedate = ?, completed = 0, last_updated_by = ?
        ';
        $db = $this->app->getDatabase();
        $statement = $db->prepare($query);
        $statement->execute([$customer->id, $task->title, $task->duedate, $task->last_updated_by]);

        $id = (int) $db->lastInsertId();

        $query = 'INSERT INTO task_queue SET task_id = ?, num_tries = 0';
        $db->prepare($query)->execute([$id]);

        $inTransaction || $db->commit();

        return $id;
    }

    public function updateTask(Task $task): void
    {
        $db = $this->app->getDatabase();

        $inTransaction = $db->inTransaction();
        $inTransaction || $db->beginTransaction();

        $query = '
            UPDATE task SET title = ?, duedate = ?, completed = ?, last_updated_by = ? WHERE id = ?
        ';
        $statement = $db->prepare($query);
        $statement->execute([$task->title, $task->duedate, (int) $task->completed, $task->last_updated_by, $task->id]);

        $query = 'REPLACE INTO task_queue SET task_id = ?, num_tries = 0';
        $db->prepare($query)->execute([$task->id]);

        $inTransaction || $db->commit();
    }

    public function deleteTask(int $taskId): void
    {
        $query = '
            DELETE FROM task WHERE id = ?
        ';
        $this->app->getDatabase()->prepare($query)->execute([$taskId]);
    }

    /**
     * @param int[] $taskIds
     *
     * @return Task[]
     */
    public function getTasks(array $taskIds): array
    {
        $db = $this->app->getDatabase();

        $ids = implode(',', array_map('intval', $taskIds));

        $query = sprintf('SELECT id, title, duedate, completed, last_updated_by FROM task WHERE id IN (%s)', $ids);
        $statement = $db->query($query);

        return $statement->fetchAll(PDO::FETCH_CLASS, Task::class) ?: [];
    }

    /**
     * @return Task[]
     */
    public function getCurrentTasks(Customer $customer): array
    {
        $db = $this->app->getDatabase();

        $query = '
            SELECT id, title, duedate, completed, last_updated_by FROM task
            WHERE customer_id = ? AND completed = 0 AND duedate < ?
            ORDER BY duedate
            LIMIT 500
        ';
        $statement = $db->prepare($query);
        $statement->execute([$customer->id, date('Y-m-d', strtotime('+1 week'))]);

        return $statement->fetchAll(PDO::FETCH_CLASS, Task::class) ?: [];
    }

    /**
     * @return Task[]
     */
    public function getCompletedTasks(Customer $customer): array
    {
        $db = $this->app->getDatabase();

        $query = '
            SELECT id, title, duedate, completed, last_updated_by FROM task
            WHERE customer_id = ? AND completed = 1
            ORDER BY duedate DESC
            LIMIT 500
        ';
        $statement = $db->prepare($query);
        $statement->execute([$customer->id]);

        return $statement->fetchAll(PDO::FETCH_CLASS, Task::class) ?: [];
    }

    /**
     * @return Task[]
     */
    public function getTasksFromQueue(): array
    {
        $db = $this->app->getDatabase();

        $query = 'SELECT task_id FROM task_queue WHERE num_tries < 20';
        $taskIds = $db->query($query)->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return $this->getTasks($taskIds);
    }

    public function updateTaskQueue(int $taskId): void
    {
        $query = 'UPDATE task_queue SET num_tries = num_tries + 1, last_try = now() WHERE task_id = ?';

        $statement = $this->app->getDatabase()->prepare($query);
        $statement->execute([$taskId]);
    }

    public function deleteTaskQueue(int $taskId): void
    {
        $query = 'DELETE FROM task_queue WHERE task_id = ?';

        $statement = $this->app->getDatabase()->prepare($query);
        $statement->execute([$taskId]);
    }
}
