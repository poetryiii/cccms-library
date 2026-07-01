<?php
declare(strict_types=1);

namespace cccms\model;

use cccms\Model;
use cccms\services\{UserService};
use think\facade\Cache;

class SysDataAuth extends Model
{
    /** 绑定维度的默认优先级：越低越优先 */
    public const PRIORITY_USER = 0;
    public const PRIORITY_POST = 100;
    public const PRIORITY_DEPT = 200;
    public const PRIORITY_ROLE = 300;

    /**
     * 获取当前用户在某表上的数据权限规则（含缓存，版本号驱动失效）
     */
    public function getUserRules(string $tableName): array
    {
        if (UserService::isAdmin()) return [];

        $userId  = UserService::getUserId();
        $version = (int)Cache::get('data_auth_version', 1);
        $key     = "da_v{$version}_{$userId}_{$tableName}";

        $cached = Cache::get($key);
        if ($cached !== null) return $cached;

        $roleIds = UserService::instance()->getUserRoleIds();
        $postIds = SysUserPost::mk()->where('user_id', $userId)->column('post_id');
        $deptIds = UserService::getUserDeptIds($userId);

        $rules = $this->where('table_name', $tableName)
            ->where('status', 1)
            ->where(function ($query) use ($userId, $roleIds, $postIds, $deptIds) {
                $query->whereOr('user_id', $userId);
                if ($roleIds) $query->whereOr('role_id', 'in', $roleIds);
                if ($postIds) $query->whereOr('post_id', 'in', $postIds);
                if ($deptIds) $query->whereOr('dept_id', 'in', $deptIds);
            })
            ->order('priority asc, id asc')
            ->select()
            ->toArray();

        Cache::set($key, $rules, 3600);
        return $rules;
    }

    /** 规则变更时版本号+1，全局失效所有用户缓存 */
    public static function onAfterWrite($model): void
    {
        parent::onAfterWrite($model);
        Cache::inc('data_auth_version');
    }

    public static function onAfterDelete($model): void
    {
        parent::onAfterDelete($model);
        Cache::inc('data_auth_version');
    }

    /** 搜索器 */
    public function searchTableNameAttr($query, $value): void
    {
        $query->where('table_name', 'like', '%' . $value . '%');
    }

    public function searchFieldAttr($query, $value): void
    {
        $query->where('field', 'like', '%' . $value . '%');
    }

    public function searchNameAttr($query, $value): void
    {
        $query->where('name', 'like', '%' . $value . '%');
    }
}
