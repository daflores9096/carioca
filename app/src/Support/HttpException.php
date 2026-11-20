<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class HttpException extends RuntimeException
{
    public function __construct(string $message, int $status = 400)
    {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        $code = $this->getCode();
        return $code >= 400 && $code < 600 ? $code : 400;
    }
}
