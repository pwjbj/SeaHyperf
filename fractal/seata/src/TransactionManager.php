<?php

declare(strict_types=1);

namespace Fractal\Seata;

use Hyperf\Snowflake\IdGeneratorInterface;
use Hyperf\Di\Annotation\Inject;
use Fractal\Seata\Context\RootContext;
use Fractal\Seata\Tc\ServiceManager;
use Fractal\Seata\At\UndoManager;

class TransactionManager
{

    protected $manager;

    /**
     * @Inject
     * @var IdGeneratorInterface
     */
    protected $idGenerator;

    /**
     * @Inject
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @Inject
     * @var RootContext
     */
    protected $rootContext;

    public function setManager($manager):self
    {
        $this->manager = $manager;
        return $this;
    }

    public function getManager()
    {
        return $this->manager;
    }

    public function makeXid(): string
    {
        $xid = (string)$this->idGenerator->generate();
        $this->rootContext->setXid($xid);
        return $xid;
    }

    public function globalCommit(string $xid):void
    {
        if($this->manager instanceof UndoManager){
            $this->serviceManager->branchCommit($this->manager, $xid);
        }

    }

    public function globalRollback(string $xid):void
    {
        if($this->manager instanceof UndoManager){
            $this->serviceManager->branchRollback($this->manager, $xid);
        }
    }


}
