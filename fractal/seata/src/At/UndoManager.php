<?php

declare(strict_types = 1);

namespace Fractal\Seata\At;

use Fractal\Seata\At\Model\UndoLog;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Database\Model\Model;
use Hyperf\Di\Annotation\Inject;
use Fractal\Seata\Context\RootContext;
use App\Model\SeaTest;
use Hyperf\DbConnection\Db;

class UndoManager
{
    //0:normal status,1:defense status
    const  NORMAL = 0;
    const  DEFENSE = 1;

    /**
     * @Inject
     * @var RootContext
     */
    protected $rootContext;

    public function recognizer(object $instance, ProceedingJoinPoint $proceedingJoinPoint)
    {
        //TODO 申请全局锁
        $undoItems = $result = [];
        //表名
        $table = $instance->getTable();
        $keyName = $instance->getKeyName();

        //操作类型
        switch ($proceedingJoinPoint->className . '::' . $proceedingJoinPoint->methodName) {
            case Model::class . '::' . 'save':
                if($instance->exists === true){
                    $sqlType = 'UPDATE';
                    $attributes = $instance->getAttributes();
                    $table_name = env('DB_PREFIX', '') . $table;
                    //事物的隔离级别至少要设置在Read Committed以上
                    $beforeData = Db::connection('default')->select("SELECT * FROM `$table_name` WHERE id = ?",[$attributes[$keyName]]);
                    $result = $proceedingJoinPoint->process();
                    $undoItems['beforeImage']['rows'][] = json_decode( json_encode( $beforeData[0]),true);
                    $undoItems['beforeImage']['tableName'] = $table;
                    $undoItems['afterImage']['rows'][] = $attributes;
                    $undoItems['afterImage']['tableName'] = $table;
                    $undoItems['sqlType'] = $sqlType;
                }else{
                    //先插入在获取属性
                    $result = $proceedingJoinPoint->process();
                    $attributes = $instance->getAttributes();
                    $sqlType = 'INSERT';
                    $undoItems['beforeImage']['rows'][] = [$keyName => $attributes[$keyName]];
                    $undoItems['beforeImage']['tableName'] = $table;
                    $undoItems['afterImage']['rows'][] = $attributes;
                    $undoItems['afterImage']['tableName'] = $table;
                    $undoItems['sqlType'] = $sqlType;
                }
                break;
            case Model::class . '::' . 'delete':
                //先获取属性, 在删除
                $attributes = $instance->getAttributes();
                $sqlType = 'DELETE';
                $undoItems['beforeImage']['rows'][] = $attributes;
                $undoItems['beforeImage']['tableName'] = $table;
                $undoItems['afterImage']['rows'][] = [$keyName => $attributes[$keyName]];
                $undoItems['afterImage']['tableName'] = $table;
                $undoItems['sqlType'] = $sqlType;
                $result = $proceedingJoinPoint->process();
                break;
        }
        return [
            $result , $undoItems
        ];
    }

    public function create($undoItems)
    {
        if(!empty($undoItems)){
            UndoLog::query()->create([
                'xid' => $this->rootContext->getXid(),
                'context' => json_encode($undoItems),
                'rollback_info' => json_encode($undoItems),
                'log_status' => self::NORMAL,
            ]);
        }
    }
}
