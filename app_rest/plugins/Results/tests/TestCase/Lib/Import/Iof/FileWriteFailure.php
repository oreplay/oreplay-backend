<?php

declare(strict_types = 1);

namespace Results\Lib\Import\Iof;

function file_put_contents(string $filename, mixed $data, int $flags = 0, mixed $context = null): int|false
{
    @trigger_error('file_put_contents(): Only 0 of ' . strlen((string)$data)
        . ' bytes written, possibly out of free disk space', E_USER_WARNING);
    return false;
}
