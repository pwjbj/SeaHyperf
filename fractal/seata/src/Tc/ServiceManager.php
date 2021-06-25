<?php

declare(strict_types=1);

namespace Fractal\Seata\Tc;

use Fractal\Seata\Context\RootContext;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\ApplicationContext;

class ServiceManager
{
    const BRANCH_REGISTER = 0; //分支注册
    const BRANCH_SUCCESS = 1; //分支运行成功
    const BRANCH_FAILED = 2;//分支运行失败

    /**
     * @Inject
     * @var RootContext
     */
    protected $rootContext;

    //注册分支事物
    public function branchRegister(string $class, string $method, array $param): void
    {
        //TODO  对服务做去重处理
        $this->rootContext->setServices([
            'class' => $class,
            'method' => $method,
            'param' => $param,
        ], self::BRANCH_REGISTER);
    }

    public function branchSuccess(string $class, string $method, array $param)
    {
        $this->rootContext->modify([
            'class' => $class,
            'method' => $method,
            'param' => $param,
        ], self::BRANCH_SUCCESS);
    }

    public function branchFailed(string $class, string $method, array $param): void
    {
        $this->rootContext->modify([
            'class' => $class,
            'method' => $method,
            'param' => $param,
        ], self::BRANCH_FAILED);
    }

    public function branchCommit($manager, $xid):void
    {
        $services = $this->rootContext->getServices();
        co(function ()use ($manager, $xid, $services){
            //当前进程下得提交
            $manager->commit($xid);
            //分支提交
            foreach ($services as $item){
                $class = ApplicationContext::getContainer()->get($item['endpoints']['class']);
                $method = 'branchCommit';
                $class->$method(...[$xid]);
            }
        });
    }

    public function branchRollback($manager, $xid):void
    {
        //当前进程下得回滚
        $manager->rollback($xid);
        //分支回滚
        $services = $this->rootContext->getServices();
        foreach ($services as $item){
            $class = ApplicationContext::getContainer()->get($item['endpoints']['class']);
            $method = 'branchRollback';
            $class->$method(...[$xid]);
        }
    }


}
