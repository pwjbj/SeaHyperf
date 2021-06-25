<?php

declare(strict_types=1);

namespace Fractal\Seata\Tc;

use Fractal\Seata\At\UndoManager;

trait AtTrait
{
    public $undoManager;

    public function __construct()
    {
        $this->undoManager = new UndoManager();
    }

    public function branchRollback($xid)
    {
        $this->undoManager->rollback($xid);
    }

    public function branchCommit($xid)
    {
        $this->undoManager->commit($xid);
    }
}
