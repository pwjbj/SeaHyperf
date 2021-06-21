<?php

declare(strict_types=1);

namespace Fractal\Seata\Tc\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\RpcClient\ServiceClient;
use Fractal\Seata\Context\RootContext;
use Fractal\Seata\Tc\TransactionManager;
use Hyperf\Di\Annotation\Inject;

/**
 * @Aspect
 */
class RpcClientAspect extends AbstractAspect
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

    public $classes = [
        ServiceClient::class . "::__call",
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $xid = $this->rootContext->getXid();
        if(!empty($xid)){
            try {
                $relation = self::guessBelongsToRelation();
                //注册分支事物
                $this->transactionManager->register($relation['class'].'::'.$relation['function']);
                $result = $proceedingJoinPoint->process();
                //分支事物完成
                $this->transactionManager->globalReport($relation['class'].'::'.$relation['function'], 1);
                return $result;
            }catch (\Throwable $e){
                //分支事物回滚
                $this->transactionManager->globalReport($relation['class'].'::'.$relation['function'], 2);
                throw new $e;
            }
        }else{
            //正常得rpc调用
            return $proceedingJoinPoint->process();
        }

    }

    protected function guessBelongsToRelation()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);
        return $backtrace[7];
    }
}
