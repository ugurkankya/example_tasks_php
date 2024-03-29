<?php

namespace TaskService\Routes;

use TaskService\Exceptions\HttpException;
use TaskService\Framework\App;
use Throwable;

class HttpRoutes
{
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function run(): void
    {
        $app = $this->app;

        $customer = $app->getAuthentication()->getCustomer(
            $app->getHeader('HTTP_AUTHORIZATION'),
            $app->getConfig()->publicKey
        );
        if (empty($customer)) {
            throw new HttpException('unauthorized', 401);
        }

        $router = $app->getRouter();

        $router->get('/v1/tasks', function () use ($app, $customer): void {
            if ($app->getParam('completed')) {
                $tasks = $app->getTasksController()->getCompletedTasks($customer);
            } else {
                $tasks = $app->getTasksController()->getCurrentTasks($customer);
            }

            $app->getOutput()->json($app->getTasksSerializer()->serializeTasks($tasks), 200);
        });

        $router->get('/v1/tasks/(\d+)', function (int $taskId) use ($app, $customer): void {
            $task = $app->getTasksController()->getTask($customer, $taskId);

            $app->getOutput()->json($app->getTasksSerializer()->serializeTask($task), 200);
        });

        $router->post('/v1/tasks', function () use ($app, $customer): void {
            $task = $app->getTasksController()->createTask(
                $customer, $app->getParam('title'), $app->getParam('duedate')
            );

            $location = sprintf('/v1/tasks/%s', $task->id);

            $app->getOutput()->json($app->getTasksSerializer()->serializeTask($task), 201, $location);
        });

        $router->put('/v1/tasks/(\d+)', function (int $taskId) use ($app, $customer): void {
            $task = $app->getTasksController()->updateTask(
                $customer, $taskId, $app->getParam('title'), $app->getParam('duedate'),
                (bool) $app->getParam('completed')
            );

            $app->getOutput()->json($app->getTasksSerializer()->serializeTask($task), 200);
        });

        $router->delete('/v1/tasks/(\d+)', function (int $taskId) use ($app, $customer): void {
            $app->getTasksController()->deleteTask($customer, $taskId);

            $app->getOutput()->noContent();
        });

        $router->any('.*', function (): void {
            throw new HttpException('not found', 404);
        });

        try {
            $router->match($app->getHeader('REQUEST_METHOD'), $app->getHeader('DOCUMENT_URI'));
        } catch (Throwable $exp) {
            $event = [
                'message' => $exp->getMessage(),
                'code' => (int) $exp->getCode(),
                'customer' => $customer->id ?? 0,
                'method' => $app->getHeader('REQUEST_METHOD'),
                'uri' => $app->getHeader('DOCUMENT_URI'),
            ];
            $app->getLogger()->log($event, $event['code']);

            throw $exp;
        }
    }
}
