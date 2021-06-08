<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller;

use Hyperf\Di\Annotation\Inject;
use App\JsonRpc\SlaveService;
use Fractal\Seata\Tc\Annotation\GlobalTransactional;

class IndexController extends AbstractController
{
    /**
     * @Inject
     * @var SlaveService
     */
    private $slave;

    /**
     * @GlobalTransactional
     */
    public function index()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();
        $res = $this->slave->slaveClient();
        return $res;
    }
}
