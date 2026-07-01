<?php
declare(strict_types=1);

namespace cccms\model;

use cccms\Model;
use think\model\relation\HasMany;

class SysCrontab extends Model
{
    public function logs(): HasMany
    {
        return $this->hasMany(SysCrontabLog::class, 'crontab_id', 'id');
    }

    public function searchTitleAttr($query, $value): void
    {
        $query->where('title', 'like', '%' . $value . '%');
    }
}
