<?php
declare(strict_types=1);

namespace cccms;

use cccms\services\{NodeService, UserService, DataService};

/**
 * @method Query _withSearch(array|string $fields, array $data = [], bool $strict = true, $value = null) 搜索器
 * @method mixed _read(mixed $data = null, ?callable $callable = null) 查找数据(支持recycle数组参数)
 * @method array _list(?array $params = null, ?callable $callable = null) 数组
 * @method array _page(?array $listRows = null, bool|int $simple = false, ?callable $callable = null) 分页查询
 * @method bool _delete(array|string $condition, $delete = null, ?callable $callable = null) 快捷删除
 */
abstract class Model extends \think\Model
{
    protected string $dataAuthField = 'user_id';

    // 默认不开启全局权限范围，子类按需覆盖
    // protected $globalScope = ['commonAuth'];

    /**
     * 创建模型实例
     * @return static
     */
    public static function mk($data = []): static
    {
        return new static($data);
    }

    /**
     * 查询后
     * @param $model
     */
    public static function onAfterRead($model)
    {
    }

    /**
     * 新增前
     * @param $model
     */
    public static function onBeforeInsert($model)
    {
    }

    /**
     * 新增后
     * @param $model
     */
    public static function onAfterInsert($model)
    {
    }

    /**
     * 更新前
     * @param $model
     */
    public static function onBeforeUpdate($model)
    {
    }

    /**
     * 更新后
     * @param $model
     */
    public static function onAfterUpdate($model)
    {
    }

    /**
     * 写入前 — 字段级只读保护
     */
    public static function onBeforeWrite($model)
    {
        if (UserService::isAdmin()) return;
        $data = DataService::instance()->getUserData($model->name);
        if (empty($data['readOnly'])) return;

        // 主键只读 → 禁止写入
        if (in_array($model->pk, $data['readOnly'])) {
            _result(['code' => 403, 'msg' => '无写入权限'], _getEnCode());
        }
        // 剥离只读字段，防止越权修改
        $writable = array_diff_key($model->getData(), array_flip($data['readOnly']));
        $model->data($writable, true);
    }

    /**
     * 写入后
     * @param $model
     */
    public static function onAfterWrite($model)
    {
    }

    /**
     * 删除前
     * @param $model
     */
    public static function onBeforeDelete($model)
    {
        // $data = DataService::instance()->getUserData($model->name);
        // // 如果字段主键只读 数据禁止删除
        // if (in_array($model->pk, $data['readOnly'])) return false;
    }

    /**
     * 删除后
     * @param $model
     */
    public static function onAfterDelete($model)
    {
    }

    /**
     * 恢复前
     * @param $model
     */
    public static function onBeforeRestore($model)
    {
    }

    /**
     * 恢复后
     * @param $model
     */
    public static function onAfterRestore($model)
    {
    }

    /**
     * 综合数据权限范围：数据归属 + 字段裁剪 + 部门过滤
     * 子类通过 $globalScope = ['commonAuth'] 启用
     */
    public function scopeCommonAuth($query): void
    {
        // 1. 用户数据归属范围
        $this->scopeUserDataAuth($query);

        // 2. 字段级权限控制
        $this->applyFieldAuth($query);

        // 3. 部门数据范围过滤
        $this->applyDeptAuth($query);
    }

    // 用户数据归属范围 (保留供子类覆盖)
    public function scopeUserDataAuth($query): void
    {
        $node = NodeService::instance()->getCurrentNodeInfo();
        if (!empty($node) && $node['auth'] && !UserService::isAdmin()) {
            $fields = $query->getTableFields();
            if (in_array($this->dataAuthField, $fields)) {
                $query->where($this->dataAuthField, 'in', function ($query) {
                    $query->table('sys_user_dept')->where('dept_id', 'in', UserService::getUserDeptIds())->field('user_id');
                });
            }
        }
    }

    /**
     * 字段级权限：隐藏字段 / 只读 / 掩码 / 条件筛选
     * 数据源：sys_data_auth 表（DataService::getUserData）
     */
    protected function applyFieldAuth($query): void
    {
        $data = DataService::instance()->getUserData($this->name);
        if (empty($data)) return;

        // 隐藏字段
        $excludedFields = array_intersect($query->getTableFields(), $data['withoutField'] ?? []);
        if (!empty($excludedFields)) $query->withoutField($excludedFields);

        // 行级条件（condition 规则的 AND 叠加）
        if (!empty($data['whereAndMap'])) $query->where($data['whereAndMap']);
        if (!empty($data['whereOrMap'])) {
            $query->where(function ($query) use ($data) {
                $query->whereOr($data['whereOrMap']);
            });
        }

        // 掩码显示
        if (!empty($data['maskShow'])) {
            foreach ($data['maskShow'] as $field) {
                $query->withAttr($field, function () {
                    return '********';
                });
            }
        }
    }

    /**
     * 部门数据范围过滤：含 dept_id 字段的表，按用户部门范围过滤
     */
    protected function applyDeptAuth($query): void
    {
        $node = NodeService::instance()->getCurrentNodeInfo();
        if (!empty($node) && $node['auth'] && !UserService::isAdmin()) {
            $fields = $query->getTableFields();
            if (in_array('dept_id', $fields)) {
                $query->where('dept_id', 'in', UserService::instance()->getUserDeptIds());
            }
        }
    }
}
