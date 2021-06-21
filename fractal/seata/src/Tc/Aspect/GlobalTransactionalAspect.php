<?php

declare(strict_types=1);

namespace Fractal\Seata\Tc\Aspect;

use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Fractal\Seata\Tc\Annotation\GlobalTransactional;
use Hyperf\Di\Annotation\Aspect;
use Fractal\Seata\Context\RootContext;
use Fractal\Seata\Tc\TransactionManager;
use Hyperf\Di\Annotation\Inject;

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

    /**
     * @Inject
     * @var TransactionManager
     */
    protected $transactionManager;


    public $annotations = [
        GlobalTransactional::class,
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $className = $proceedingJoinPoint->className;
        $methodName = $proceedingJoinPoint->methodName;
        //生成全局事物
        $xid = $this->transactionManager->makeXid();
        //传递xid
        $this->rootContext->setXid($xid);
        try {
            //注册分支事物
            $this->transactionManager->register($className.'::'.$methodName);
            $result = $proceedingJoinPoint->process();
            $this->transactionManager->globalReport($className.'::'.$methodName, 1);
            //所有分支事物都注册完成
            $this->transactionManager->commit();
            //todo 删除日志文件
            return $result;
        }catch (\Throwable $e){
            var_dump($e->getMessage());
            var_dump($e->getLine());
            var_dump($e->getFile());
            var_dump($e->getCode());
            $this->transactionManager->globalReport($className.'::'.$methodName, 2);
            $this->transactionManager->rollback();
//            throw new TransactionException();
        }

    }



}
