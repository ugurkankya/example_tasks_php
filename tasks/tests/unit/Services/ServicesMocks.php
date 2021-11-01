<?php

namespace TaskService\Services;

/**
 * @param mixed[] ...$params
 */
function mail(...$params): bool
{
    ServicesMocks::$mailParams = $params;

    return ServicesMocks::$mailReturn;
}

abstract class ServicesMocks
{
    public static bool $mailReturn = true;

    /** @var mixed[] */
    public static array $mailParams = [];
}
