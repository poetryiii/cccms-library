<?php
declare(strict_types=1);

namespace cccms\services;

use cccms\Service;
use cccms\model\SysDataAuth;

class DataService extends Service
{
    /** 供前端选择的条件操作符列表 */
    public function getOperatorList(): array
    {
        return [
            'eq'            => '等于(=)',
            'neq'           => '不等于(<>)',
            'gt'            => '大于(>)',
            'egt'           => '大于等于(>=)',
            'lt'            => '小于(<)',
            'elt'           => '小于等于(<=)',
            'in'            => '在范围内(IN)',
            'not_in'        => '不在范围内(NOT IN)',
            'between'       => '在区间内(BETWEEN)',
            'not_between'   => '不在区间内(NOT BETWEEN)',
            'null'          => '为NULL',
            'not_null'      => '不为NULL',
            'empty_string'  => '为空字符串',
            'not_empty_string' => '不为空字符串',
            'left_like'     => '左模糊(%x)',
            'right_like'    => '右模糊(x%)',
            'all_like'      => '全模糊(%x%)',
        ];
    }
    /**
     * 获取条件映射
     * @param string $where
     * @return array
     */
    public function getWhereCorresponding(string $where): array
    {
        return [
            'hidden' => ['name' => '隐藏', 'where' => '', 'format' => ''],
            'read_only' => ['name' => '只读', 'where' => '', 'format' => ''],
            'mask_show' => ['name' => '掩码显示', 'where' => '', 'format' => ''],
            'eq' => ['name' => '等于', 'where' => '=', 'format' => ''],
            'neq' => ['name' => '不等于', 'where' => '<>', 'format' => ''],
            'lt' => ['name' => '小于', 'where' => '<', 'format' => ''],
            'elt' => ['name' => '小于等于', 'where' => '<=', 'format' => ''],
            'gt' => ['name' => '大于', 'where' => '>', 'format' => ''],
            'egt' => ['name' => '大于等于', 'where' => '>=', 'format' => ''],
            'null' => ['name' => '为NULL', 'where' => 'null', 'format' => ''],
            'not_null' => ['name' => '为NULL(NOT)', 'where' => 'not null', 'format' => ''],
            'empty_string' => ['name' => '为空值', 'where' => '=', 'format' => ''],
            'not_empty_string' => ['name' => '为空值(NOT)', 'where' => '<>', 'format' => ''],
            'in' => ['name' => '在范围内', 'where' => 'in', 'format' => ''],
            'not_in' => ['name' => '在范围内(NOT)', 'where' => 'not in', 'format' => ''],
            'between' => ['name' => '在区间内', 'where' => 'between', 'format' => ''],
            'not_between' => ['name' => '在区间内(NOT)', 'where' => 'not between', 'format' => ''],
            'left_like' => ['name' => '左模糊', 'where' => 'like', 'format' => '%#[value]#'],
            'not_left_like' => ['name' => '左模糊(NOT)', 'where' => 'not like', 'format' => '%#[value]#'],
            'right_like' => ['name' => '右模糊', 'where' => 'like', 'format' => '#[value]#%'],
            'not_right_like' => ['name' => '右模糊(NOT)', 'where' => 'not like', 'format' => '#[value]#%'],
            'all_like' => ['name' => '全模糊', 'where' => 'like', 'format' => '%#[value]#%'],
            'not_all_like' => ['name' => '全模糊(NOT)', 'where' => 'not like', 'format' => '%#[value]#%'],
            'custom_like' => ['name' => '自定义模糊', 'where' => 'like', 'format' => '#[value]#'],
            'not_custom_like' => ['name' => '自定义模糊(NOT)', 'where' => 'not like', 'format' => '#[value]#'],
        ][$where] ?? [];
    }

    /**
     * 处理条件
     * @param string $field
     * @param string $where
     * @param string|int $value
     * @return array
     */
    public function handleWhere(string $field, string $where, string|int $value): array
    {
        if (empty($corresponding = $this->getWhereCorresponding($where))) return [];
        if (in_array($where, ['in', 'not_in', 'between', 'not_between'])) {
            $value = explode(',', $value);
        } elseif (str_contains($where, 'like')) {
            $value = str_replace('#[value]#', $value, $corresponding['format']);
        } elseif ($where == 'null') {
            return [$field, 'null'];
        } elseif ($where == 'not_null') {
            return [$field, 'not null'];
        } elseif (in_array($where, ['hidden', 'read_only', 'mask_show'])) {
            return [];
        }
        return [$field, $corresponding['where'], $value];
    }

    /**
     * 获取用户在指定表上的字段级权限
     * 数据源：sys_data_auth 表，按角色/部门/岗位/用户四个维度绑定
     * @param string $table 表名（Model::$name）
     * @return array ['withoutField' => [...], 'readOnly' => [...]]
     */
    public function getUserData(string $table = ''): array
    {
        if (empty($table)) return [];

        $rules = SysDataAuth::mk()->getUserRules($table);
        if (empty($rules)) return [];

        $hidden   = [];
        $readonly = [];
        $maskShow = [];
        $whereAnd = [];

        // priority 升序，先到先占位
        foreach ($rules as $rule) {
            $f = $rule['field'];
            switch ($rule['rule_type']) {
                case 'hidden':
                    if (!isset($hidden[$f])) $hidden[$f] = 0;
                    break;
                case 'readonly':
                    if (!isset($readonly[$f])) $readonly[$f] = 0;
                    break;
                case 'mask_show':
                    if (!isset($maskShow[$f])) $maskShow[$f] = 0;
                    break;
                case 'condition':
                    if (!empty($rule['rule_operator'])) {
                        $cond = $this->handleWhere($f, $rule['rule_operator'], $rule['rule_value'] ?? '');
                        if ($cond) $whereAnd[md5(serialize($cond))] = $cond;
                    }
                    break;
            }
        }

        return [
            'fields'       => [],
            'withoutField'  => array_keys($hidden),
            'readOnly'      => array_keys($readonly),
            'maskShow'      => array_keys($maskShow),
            'whereAndMap'   => array_values($whereAnd),
            'whereOrMap'    => [],
        ];
    }
}
