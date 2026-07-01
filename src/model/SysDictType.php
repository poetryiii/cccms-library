<?php
declare(strict_types=1);

namespace cccms\model;

use cccms\Model;
use think\model\relation\HasMany;

class SysDictType extends Model
{
    public function items(): HasMany
    {
        return $this->hasMany(SysDictData::class, 'type_id', 'id');
    }

    public function searchDictTypeAttr($query, $value): void
    {
        $query->where('dict_type', 'like', '%' . $value . '%');
    }

    public function searchDictNameAttr($query, $value): void
    {
        $query->where('dict_name', 'like', '%' . $value . '%');
    }
}
