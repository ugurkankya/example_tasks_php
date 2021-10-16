<?php

/**
 * @var TaskService\Services\EmailService $this
 * @var TaskService\Views\TaskCompletedEmail $email
 */
$service = $this;

?>
<!DOCTYPE html>
<html>
    <body>
    	Task <b><?= $service->e($email->task->title); ?></b> completed!
    </body>
</html>