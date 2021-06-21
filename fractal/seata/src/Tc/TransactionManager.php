<?php

declare(strict_types=1);

namespace Fractal\Seata\Tc;

use Hyperf\Snowflake\IdGeneratorInterface;
use Hyperf\Di\Annotation\Inject;
use Fractal\Seata\Context\RootContext;

class TransactionManager
{
    /**
     * @Inject
     * @var IdGeneratorInterface
     */
    protected $idGenerator;

    /**
     * @Inject
     * @var RootContext
     */
    protected $rootContext;

    public function makeXid(): string
    {
        return (string)$this->idGenerator->generate();
    }

    //注册分支事物
    public function register(string $service): void
    {
        $this->rootContext->setServices([
            'service' => $service,
            'status' => 0,
        ]);

        //分支事物id
    }

    //报告事物执行状态
    public function globalReport(string $service, int $status): void
    {
        $this->rootContext->modify($service, $status);
    }

    public function commit()
    {

    }

    public function rollback()
    {

    }


}
