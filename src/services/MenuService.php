<?php
declare(strict_types=1);

namespace cccms\services;

use cccms\Service;
use cccms\model\SysMenu;
use cccms\extend\ArrExtend;

class MenuService extends Service
{
    public function getAllMenus(): array
    {
        return SysMenu::mk()->withoutField('create_time,update_time')->cache(600)->_list();
    }

    /**
     * 判断指定菜单是否有子菜单
     * @param int $parent_id 菜单ID
     * @return array
     */
    public function isMenuChildren(int $parent_id = 0): array
    {
        return ArrExtend::toChildrenIds($this->getAllMenus(), $parent_id, 'id', 'parent_id');
    }
}
