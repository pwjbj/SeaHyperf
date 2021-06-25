<?php

declare(strict_types=1);

namespace Fractal\Seata\Tc\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\RpcClient\ServiceClient;
use Fractal\Seata\Context\RootContext;
use Fractal\Seata\Tc\ServiceManager;
use Hyperf\Di\Annotation\Inject;
use Fractal\Seata\At\UndoManager;
use Fractal\Seata\Exception\BranchTransactionException;

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

    public $classes = [
        ServiceClient::class . "::__call",
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $xid = $this->rootContext->getXid();
        $relation = self::guessBelongsToRelation();
        if(!empty($xid) && $relation['function'] != 'branchRollback' && $relation['function'] != 'branchCommit'){
            try {
                //注册分支事物
                $serviceManager = new ServiceManager();
                $class = explode('_', $relation['class'])[0];
                $serviceManager->branchRegister($class, $relation['function'], $proceedingJoinPoint->arguments['keys']['params']);
                $result = $proceedingJoinPoint->process();
                //分支事物完成
                $serviceManager->branchSuccess($class, $relation['function'], $proceedingJoinPoint->arguments['keys']['params']);
                return $result;
            }catch (\Throwable $e){
                //分支事物失败
                $serviceManager->branchFailed($class, $relation['function'], $proceedingJoinPoint->arguments['keys']['params']);
                throw new BranchTransactionException;
            }
        }else{
            //正常得rpc调用
            return $proceedingJoinPoint->process();
        }

    }

    protected function guessBelongsToRelation()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 9);
//        var_dump($backtrace);
        return $backtrace[7];
    }
}
