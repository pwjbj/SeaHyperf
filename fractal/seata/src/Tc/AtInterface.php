<?php

declare(strict_types=1);

namespace Fractal\Seata\Tc;


interface AtInterface
{
    public function branchRollback($xid);
    public function branchCommit($xid);
}
