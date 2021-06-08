<?php

declare(strict_types=1);

namespace Fractal\Seata\Tc\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class GlobalTransactional extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $GlobalTransactional;

    public function __construct($value = null)
    {
        parent::__construct($value);
    }

}
