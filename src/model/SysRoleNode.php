<?php

declare(strict_types=1);

namespace cccms\model;

use cccms\Model;
use cccms\services\{UserService, NodeService};

class SysRoleNode extends Model
{
    /**
     * 【RBAC补全】获取用户权限节点：合并部门角色 + 用户直连角色
     * @param int $user_id
     * @return array
     */
    public function getUserNodes(int $user_id = 0): array
    {
        if (UserService::isAdmin()) return NodeService::instance()->getNodes();
        $user_id = $user_id ?: UserService::getUserId();
        
        // 获取所有启用的角色ID
        $allActiveRoleIds = SysRole::mk()->getAllOpenRoleIds();
        
        // 1. 通过部门获得的角色
        $deptRoleIds = $this->where('role_id', 'in', function ($query) use ($user_id) {
            $userDeptIds = UserService::instance()->getUserDeptIds($user_id, true);
            return $query->table('sys_dept_role')->field('role_id')->where('dept_id', 'in', $userDeptIds);
        })->column('role_id');
        
        // 2. 【RBAC补全】用户直连角色
        $directRoleIds = SysUserRole::mk()->where('user_id', $user_id)->column('role_id');
        
        // 合并部门角色和直连角色，去重并过滤禁用的角色
        $roleIds = array_unique(array_merge($deptRoleIds, $directRoleIds));
        $roleIds = array_intersect($roleIds, $allActiveRoleIds);
        
        if (empty($roleIds)) return [];
        
        return $this->where('role_id', 'in', $roleIds)->column('node');
    }
}
