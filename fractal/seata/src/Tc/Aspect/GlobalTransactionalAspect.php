<?php

declare(strict_types=1);

namespace Fractal\Seata\Tc\Aspect;

use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Fractal\Seata\Tc\Annotation\GlobalTransactional;
use Hyperf\Di\Annotation\Aspect;
use Fractal\Seata\Context\RootContext;
use Hyperf\Di\Annotation\Inject;
use Fractal\Seata\Tc\TransactionManager;
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
        $className = $proceedingJoinPoint->className;
        $methodName = $proceedingJoinPoint->methodName;
        //生成全局事物
        $transactionManager = new TransactionManager(new UndoManager());
        $xid = $transactionManager->makeXid();
        //传递xid
        $this->rootContext->setXid($xid);
        try {
            //注册分支事物
            $transactionManager->register($className.'::'.$methodName);
            //TODO 加入waitGroup
            $result = $proceedingJoinPoint->process();
            $transactionManager->globalReport($className.'::'.$methodName, 1);
            //所有分支事物都注册完成
            $transactionManager->commit($xid);
            return $result;
        }catch (\Throwable $e){
            var_dump($e->getMessage());
            $transactionManager->globalReport($className.'::'.$methodName, 2);
            $transactionManager->rollback($xid);
//            throw new TransactionException();
        }

    }



}
