<?php

declare(strict_types=1);

namespace Fractal\Seata\Exception;

class BranchTransactionException extends \Exception
{
    public function __construct($message = 'branch transaction failed', $code = 6003, \Throwable $previous = null)
    {
        parent::__construct($message, $code);
    }
}
