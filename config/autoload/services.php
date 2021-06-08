<?php

$registry = [
    'protocol' => 'consul',
    'address' => 'http://' . env('CONSUL_HOST') . ':' . env('CONSUL_PORT', 8500),
];
return [

    'consumers' => [
        [
            'name' => 'slave.client',
            'service' => \App\JsonRpc\SlaveService::class,
            'protocol' => 'jsonrpc',

            // 这个消费者要从哪个服务中心获取节点信息，如不配置则不会从服务中心获取节点信息
            'registry' => $registry,

            'options' => [
                'connect_timeout' => 5.0,
                'recv_timeout' => 5.0,
                'settings' => [
                    // 根据协议不同，区分配置
                    'open_eof_split' => true,
                    'package_eof' => "\r\n",
                ],
                'retry_count' => 2,
                'retry_interval' => 100,
                'pool' => [
                    'min_connections' => 1,
                    'max_connections' => 32,
                    'connect_timeout' => 10.0,
                    'wait_timeout' => 3.0,
                    'heartbeat' => -1,
                    'max_idle_time' => 60.0,
                ],
            ],
        ]
    ],
];
