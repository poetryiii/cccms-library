<?php
declare(strict_types=1);

namespace cccms\model;

use think\model\Pivot;

class SysUserPost extends Pivot
{
    protected $autoWriteTimestamp = false;

    public static function mk($data = []): static
    {
        return new static($data);
    }
}
