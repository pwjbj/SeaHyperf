<?php

declare(strict_types=1);

namespace Fractal\Seata\Context;

use Hyperf\Rpc\Context as RpcContent;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Snowflake\IdGeneratorInterface;

class RootContext
{
    /**
     * @Inject
     * @var IdGeneratorInterface
     */
    protected $idGenerator;

    /**
     * @Inject
     * @var RpcContent
     */
    protected $rpcContent;

    public function getXid()
    {

    }
}
