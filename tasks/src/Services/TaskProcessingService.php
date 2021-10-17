<?php

namespace TaskService\Services;

use TaskService\Framework\App;
use TaskService\Models\Task;
use TaskService\Views\TaskCompletedEmail;

class TaskProcessingService
{
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function processTaskUpdate(Task $task): void
    {
        if ($task->completed) {
            $email = new TaskCompletedEmail();
            $email->task = $task;
            $email->subject = sprintf($email->subject, $task->id);
            $email->to = $task->last_updated_by;

            $this->app->getEmailService()->sendEmail($email);
        }
    }
}
