<?php

declare(strict_types=1);

namespace Fractal\Seata\Tc\Aspect;

use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Fractal\Seata\Tc\Annotation\GlobalTransactional;
use Hyperf\Di\Annotation\Aspect;

/**
 * @Aspect
 */
class GlobalTransactionalAspect extends AbstractAspect
{
    public $annotations = [
        GlobalTransactional::class,
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $className = $proceedingJoinPoint->className;
        $methodName = $proceedingJoinPoint->methodName;
        $result = $proceedingJoinPoint->process();
        return $result;
    }

}
