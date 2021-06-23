<?php

declare(strict_types=1);

namespace Fractal\Seata\Exception;

class UndoLogDiffException extends \Exception
{
    public function __construct($message = 'undo log different', $code = 6004, \Throwable $previous = null)
    {
        parent::__construct($message, $code);
    }
}
