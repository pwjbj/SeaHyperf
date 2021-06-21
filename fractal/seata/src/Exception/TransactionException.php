<?php

declare(strict_types=1);

namespace Fractal\Seata\Exception;

class TransactionException extends \Exception
{
    public function __construct($message = 'transaction failed', $code = 6002, \Throwable $previous = null)
    {
        parent::__construct($message, $code);
    }
}
