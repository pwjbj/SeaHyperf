<?php

declare(strict_types=1);

namespace Fractal\Seata\At;

use Fractal\Seata\At\Model\UndoLog;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Database\Model\Model;
use Hyperf\Di\Annotation\Inject;
use Fractal\Seata\Context\RootContext;
use Hyperf\DbConnection\Db;
use Fractal\Seata\Exception\UndoLogDiffException;

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

    public function recognizer(object $instance, ProceedingJoinPoint $proceedingJoinPoint): array
    {
        //TODO 申请全局锁
        $undoItems = $result = [];
        //表名
        $table = $instance->getTable();
        $keyName = $instance->getKeyName();

        //操作类型 c u d
        switch ($proceedingJoinPoint->className . '::' . $proceedingJoinPoint->methodName) {
            case Model::class . '::' . 'save':
                if ($instance->exists === true) {
                    $sqlType = 'UPDATE';
                    $attributes = $instance->getAttributes();
                    $tableName = env('DB_PREFIX', '') . $table;

                    //事物的隔离级别至少要设置在Read Committed以上
                    $database = config('seata.at', 'default');
                    $beforeData = Db::connection($database)->select("SELECT * FROM `$tableName` WHERE id = ? for update", [$attributes[$keyName]]);

                    $result = $proceedingJoinPoint->process();

                    $undoItems['beforeImage']['rows'] = json_decode(json_encode($beforeData[0]), true);
                    $undoItems['beforeImage']['primaryKey'] = $keyName;
                    $undoItems['beforeImage']['tableName'] = $table;
                    $undoItems['afterImage']['rows'] = $instance->getAttributes();
                    $undoItems['afterImage']['tableName'] = $table;
                    $undoItems['afterImage']['primaryKey'] = $keyName;
                    $undoItems['sqlType'] = $sqlType;
                } else {
                    //先插入在获取属性
                    $result = $proceedingJoinPoint->process();
                    $attributes = $instance->getAttributes();
                    $sqlType = 'INSERT';
                    $undoItems['beforeImage']['rows'] = [$keyName => $attributes[$keyName]];
                    $undoItems['beforeImage']['tableName'] = $table;
                    $undoItems['beforeImage']['primaryKey'] = $keyName;
                    $undoItems['afterImage']['rows'] = $attributes;
                    $undoItems['afterImage']['tableName'] = $table;
                    $undoItems['afterImage']['primaryKey'] = $keyName;
                    $undoItems['sqlType'] = $sqlType;
                }
                break;
            case Model::class . '::' . 'delete':
                //先获取属性, 在删除
                $attributes = $instance->getAttributes();
                $sqlType = 'DELETE';
                $undoItems['beforeImage']['rows'] = $attributes;
                $undoItems['beforeImage']['tableName'] = $table;
                $undoItems['beforeImage']['primaryKey'] = $keyName;
                $undoItems['afterImage']['rows'] = [$keyName => $attributes[$keyName]];
                $undoItems['afterImage']['tableName'] = $table;
                $undoItems['afterImage']['primaryKey'] = $keyName;
                $undoItems['sqlType'] = $sqlType;
                $result = $proceedingJoinPoint->process();
                break;
        }

        return [
            $result, $undoItems
        ];
    }

    public function flushUndoLogs(array $undoItems): void
    {
        if (!empty($undoItems)) {
            UndoLog::query()->create([
                'xid' => $this->rootContext->getXid(),
                'context' => json_encode($undoItems),
                'rollback_info' => json_encode($undoItems),
                'log_status' => self::NORMAL,
            ]);
        }
    }

    public function commit(string $xid): void
    {

    }

    public function rollback(string $xid): void
    {
        try {
            Db::beginTransaction();
            $database = config('seata.at', 'default');
            $prefix = env('DB_PREFIX', '');
            $undoLog = $prefix . 'undo_log';
            $undoLogs = Db::connection($database)->select("SELECT * FROM `$undoLog` WHERE log_status= ? and xid = ? for update", [self::NORMAL, $xid]);

            if ($undoLogs) {
                $rollbackInfo = json_decode($undoLogs[0]->rollback_info, true);
                $undoLogId = $undoLogs[0]->id;

                $afterTableName = $prefix . $rollbackInfo['afterImage']['tableName'];
                $afterPrimaryKey = $rollbackInfo['afterImage']['primaryKey'];
                $beforeTableName = $prefix . $rollbackInfo['beforeImage']['tableName'];
                $beforePrimaryKey = $rollbackInfo['beforeImage']['primaryKey'];

                //当前数据
                $afterData = Db::connection($database)->select("SELECT * FROM `$afterTableName` WHERE $afterPrimaryKey = ? for update", [$rollbackInfo['afterImage']['rows'][$afterPrimaryKey]]);
                switch ($rollbackInfo['sqlType']) {
                    case 'UPDATE':
                        //验证是否可以回滚
                        $this->compareData((array)$afterData[0], $rollbackInfo['afterImage']['rows']);
                        //拼接回滚sql
                        $updateSql = "UPDATE $beforeTableName set ";
                        $updateValues = [];
                        foreach ($rollbackInfo['beforeImage']['rows'] as $k => $v) {
                            //更新除主键之外的属性
                            if ($k != $beforePrimaryKey) {
                                $updateSql .= "`$k` = ? ,";
                                $updateValues[] = $v;
                            }
                        }
                        $updateSql = rtrim($updateSql, ',') . " where $beforePrimaryKey = ?";
                        $updateValues[] = $rollbackInfo['beforeImage']['rows'][$beforePrimaryKey];
                        //回滚
                        Db::connection($database)->update($updateSql, $updateValues);
                        break;
                    case 'INSERT':
                        $this->compareData((array)$afterData[0], $rollbackInfo['afterImage']['rows']);
                        //回滚
                        Db::connection($database)->delete("DELETE FROM `$afterTableName` WHERE $afterPrimaryKey = ?", [$rollbackInfo['afterImage']['rows'][$afterPrimaryKey]]);
                        break;
                    case 'DELETE':
                        //验证能否回滚
                        if (!empty($afterData)) {
                            throw new UndoLogDiffException();
                        }
                        //回滚
                        $insertFields = array_keys($rollbackInfo['beforeImage']['rows']);
                        $insertValues = array_values($rollbackInfo['beforeImage']['rows']);
                        $strArr = array_pad([], count($insertFields), '?');
                        $insertSql = "INSERT INTO $beforeTableName (" . implode(',', $insertFields) . ") VALUES (" . implode(',', $strArr) . ")";
                        Db::connection($database)->insert($insertSql, $insertValues);
                        break;
                }
                //标记日志会馆完成
                Db::connection($database)->update("UPDATE  `$undoLog` set `log_status` = ?  WHERE id = ? ", [self::DEFENSE, $undoLogId]);
            }
            Db::commit();
        } catch (UndoLogDiffException $e) {
            Db::rollBack();
            //TODO 根据策略做不同的处理
        } catch (\Throwable $e) {
            var_dump($e->getMessage());
            Db::rollBack();
        }


    }

    private function compareData($data1, $data2)
    {
        //比较两个数组的k 和 v 是否都相等
        if (count($data1) !== count($data2)) {
            throw new UndoLogDiffException();
        }
        foreach ($data1 as $k => $v) {
            if (!isset($data2[$k]) || $data2[$k] !== $v) {
                throw new UndoLogDiffException();
            }
        }
    }
}
