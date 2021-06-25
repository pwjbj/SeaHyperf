<?php

declare(strict_types=1);

namespace App\JsonRpc;

use Fractal\Seata\Tc\AtInterface;

interface SlaveService extends AtInterface
{
    public function slaveClient($a , $b);
}
