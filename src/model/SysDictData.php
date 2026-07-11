<?php
declare(strict_types=1);

namespace cccms\model;

use cccms\Model;

class SysDictData extends Model
{
    public function searchTypeIdAttr($query, $value): void
    {
        $query->where('type_id', '=', $value);
    }
}
