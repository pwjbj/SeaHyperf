<?php

return [
    'uri' => 'http://' . env('CONSUL_HOST', '127.0.0.1') . ':' . env('CONSUL_PORT', 8500),
];
