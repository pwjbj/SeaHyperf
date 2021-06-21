<?php

declare(strict_types=1);

namespace Fractal\Seata\Context;

use Hyperf\Rpc\Context as RpcContent;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Snowflake\IdGeneratorInterface;
use Fractal\Seata\Exception\XidNotFoundException;

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

    public function getXid(): string
    {
        $xid = $this->rpcContent->get('xid', '');
        if(empty($xid)){
            throw new XidNotFoundException;
        }
        return $xid;
    }

    public function setXid(string $xid): void
    {
        $this->rpcContent->set('xid', $xid);
    }

    public function getServices(): array
    {
        $xid = $this->getXid();
        return $this->rpcContent->get($xid, []);
    }

    public function setServices(array $service): void
    {
        $xid = $this->getXid();
        $registerService = $this->getServices();
        $registerService[] = $service;
        $this->rpcContent->set($xid, $registerService);
    }

    public function modify(string $service, int $status): void
    {
        $xid = $this->getXid();
        $services = $this->rpcContent->get($xid);
        foreach($services as &$item){
            if($item['service'] == $service){
                $item['status'] = $status;
            }
        }
        $this->rpcContent->set($xid, $services);
    }
}
