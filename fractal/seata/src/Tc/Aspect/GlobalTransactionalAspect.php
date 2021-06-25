<?php

declare(strict_types=1);

namespace Fractal\Seata\Tc\Aspect;

use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Fractal\Seata\Tc\Annotation\GlobalTransactional;
use Hyperf\Di\Annotation\Aspect;
use Fractal\Seata\Context\RootContext;
use Hyperf\Di\Annotation\Inject;
use Fractal\Seata\TransactionManager;
use Fractal\Seata\At\UndoManager;

/**
 * @Aspect
 */
class GlobalTransactionalAspect extends AbstractAspect
{
    /**
     * @Inject
     * @var RootContext
     */
    protected $rootContext;

    public $annotations = [
        GlobalTransactional::class,
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        //生成全局事物
        $transactionManager = (new TransactionManager())->setManager(new UndoManager());
        $xid = $transactionManager->makeXid();
        try {
            $result = $proceedingJoinPoint->process();
            $transactionManager->globalCommit($xid);
            return $result;
        }catch (\Throwable $e){
            $transactionManager->globalRollback($xid);
        }

    }



}
