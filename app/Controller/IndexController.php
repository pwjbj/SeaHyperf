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
use App\Model\SeaTest;
use Hyperf\DbConnection\Db;

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
//        Db::beginTransaction();
        $a = SeaTest::query()->where('name', '新增')->first();
        $a->name = '2323222';
        $a->save();
//        SeaTest::query()->where('id',1)->update([
//            'name' => 'hshshs'
//        ]);
//        SeaTest::query()->create([
//            'name' => '新增'
//        ]);
//        SeaTest::query()->create([
//            'name' => '新增2'
//        ]);
//        $b = new SeaTest();
//        $b->name = '新增';
//        $b->save();
//        $a = SeaTest::query()->where('name', '新增')->get();
//        foreach ($a as $b){
//            $b->name = $b->name . $b->id;
//            $b->save();
//        }
//        $c = SeaTest::query()->where('name', '新增')->first();
//        $c->delete();
//        Db::rollBack();
//        $res = $this->slave->slaveClient();
        return 1;
    }
}
