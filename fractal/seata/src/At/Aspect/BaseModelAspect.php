<?php

declare(strict_types=1);

namespace Fractal\Seata\At\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Database\Model\Model;
use Fractal\Seata\Context\RootContext;
use Fractal\Seata\Exception\BranchTransactionException;
use Fractal\Seata\Tc\TransactionManager;
use Hyperf\Di\Annotation\Inject;
use Fractal\Seata\At\UndoManager;
use Hyperf\DbConnection\Db;

/**
 * @Aspect
 */
class BaseModelAspect extends AbstractAspect
{
    /**
     * @Inject
     * @var RootContext
     */
    protected $rootContext;

    /**
     * @Inject
     * @var UndoManager
     */
    protected $undo;

    /**
     * @Inject
     * @var TransactionManager
     */
    protected $transactionManager;

    public $classes = [
        Model::class . "::save",
        Model::class . "::delete",
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $xid = $this->rootContext->getXid();
        $instance = $proceedingJoinPoint->getInstance();
        if (!empty($xid) && $instance->getTable() != 'undo_log') {
            try {
                //提交本地事物
                Db::beginTransaction();
                list($result, $undoItems) = $this->undo->recognizer($instance, $proceedingJoinPoint);
                $this->undo->create($undoItems);
                Db::commit();
                return $result;
            }catch (\Throwable $e){
                //触发回滚
                throw new BranchTransactionException;
            }

        } else {
            return $proceedingJoinPoint->process();
        }
    }

    public function recognizer(object $instance, ProceedingJoinPoint $proceedingJoinPoint)
    {
        //表名
        $table = $instance->getTable();
        //操作类型
        switch ($proceedingJoinPoint->className . '::' . $proceedingJoinPoint->methodName) {
            case Model::class . '::' . 'save':
                $sqlType = $instance->exists === true ? 'UPDATE' : 'INSERT';
                break;
            case Model::class . '::' . 'delete':
                $sqlType = 'DELETE';
                $undoItems = [];
                $keyName = $instance->getKeyName();
                $attributes = $instance->getAttributes();
                $undoItems['beforeImage']['rows'][] = $attributes;
                $undoItems['beforeImage']['tableName'] = $table;
                $undoItems['afterImage']['rows'][] = [$keyName => $attributes];
                $undoItems['afterImage']['tableName'] = $table;
                $undoItems['sqlType'] = $sqlType;
                break;
        }

        return $undoItems;
    }


}