<?php
declare(strict_types=1);

namespace cccms\model;

use cccms\Model;
use think\model\relation\BelongsToMany;

class SysPost extends Model
{
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(SysUser::class, SysUserPost::class, 'user_id', 'post_id');
    }

    public function searchPostNameAttr($query, $value): void
    {
        $query->where('post_name', 'like', '%' . $value . '%');
    }
}
