<?php

declare (strict_types=1);

namespace Fractal\Seata\At\Model;

use Hyperf\DbConnection\Model\Model;
/**
 * @property int $id
 * @property string $xid
 * @property string $context
 * @property string $rollback_info
 * @property int $log_status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class UndoLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'undo_log';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'xid', 'context', 'rollback_info', 'log_status'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'log_status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
