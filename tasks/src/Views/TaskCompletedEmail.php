<?php

namespace TaskService\Views;

use TaskService\Models\Email;
use TaskService\Models\Task;

class TaskCompletedEmail extends Email
{
    public string $template = __DIR__ . '/TaskCompletedEmailTemplate.php';

    public string $from = 'Task Service <task.service@invalid.local>';

    public string $subject = 'Task #%s completed';

    public Task $task;
}
