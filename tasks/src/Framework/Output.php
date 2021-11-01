<?php

namespace TaskService\Framework;

class Output
{
    /**
     * @param mixed[] $data
     */
    public function json(array $data, int $code, string $location = ''): void
    {
        http_response_code($code);
        header('Content-Type: application/json');

        if ($location !== '') {
            header('Location: ' . $location);
        }

        echo $this->escape($data);
    }

    public function noContent(): void
    {
        http_response_code(204);
    }

    /**
     * @param mixed[] $data
     * @psalm-taint-escape html
     * @psalm-taint-escape has_quotes
     */
    protected function escape(array $data): string
    {
        return json_encode($data) ?: '';
    }
}
