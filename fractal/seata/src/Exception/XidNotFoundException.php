<?php

declare(strict_types=1);

namespace Fractal\Seata\Exception;

class XidNotFoundException extends \Exception
{
    public function __construct($message = 'xid not found', $code = 6001, \Throwable $previous = null)
    {
        parent::__construct($message, $code);
    }

}
