<?php

declare(strict_types=1);

namespace cccms\extend;

class ArrExtend
{
    /**
     * 递归合并多维数组
     */
    public static function recursionMergeArray($arr1, $arr2)
    {
        $merged = $arr1;
        foreach ($arr2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = static::recursionMergeArray($merged[$key], $value);
            } elseif (is_numeric($key)) {
                if (!in_array($value, $merged)) {
                    $merged[] = $value;
                }
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

    /**
     * 字符串转数组
     * @param string $text 待转内容
     * @param string $splitStr 分隔字符
     * @param null|array $allow 限定规则
     * @return array
     */
    public static function str2arr(string $text, string $splitStr = ',', ?array $allow = null): array
    {
        $items = [];
        foreach (explode($splitStr, trim($text, $splitStr)) as $item) {
            if ($item !== '' && (!is_array($allow) || in_array($item, $allow))) {
                $items[] = trim($item);
            }
        }
        return $items;
    }

    /**
     * 数组转字符串
     * @param array $data 待转数组
     * @param string $splitStr 分隔字符
     * @param null|array $allow 限定规则
     * @return string
     */
    public static function arr2str(array $data, string $splitStr = ',', ?array $allow = null): string
    {
        foreach ($data as $key => $item) {
            if ($item === '' || (is_array($allow) && !in_array($item, $allow))) {
                unset($data[$key]);
            }
        }
        return $splitStr . join($splitStr, $data) . $splitStr;
    }

    /**
     * 给定 一维数组 键 生成二维数组
     * @param array $array 数组
     * @param string $key 键
     * @return array
     */
    public static function createTwoArray(array $array, string $key): array
    {
        return array_map(function ($item) use ($key) {
            return [$key => $item];
        }, $array);
    }

    /**
     * 二维数组根据某个字段排序
     * @param array $array 待处理数据
     * @param string $field 字段
     * @param string $sort 排序 SORT_ASC 升序|SORT_DESC 降序
     * @return array
     */
    public static function toSort(array $array = [], string $field = '', string $sort = 'desc'): array
    {
        $sort = ['desc' => SORT_DESC, 'asc' => SORT_ASC][$sort] ?? SORT_DESC;
        if (empty($field)) return $array;
        $list = array_column($array, $field);
        array_multisort($list, $sort, $array);
        return $array;
    }

    /**
     * 多层树形/多维数组按字段排序（支持点语法）
     *
     * @param array  $array      源数组
     * @param string $fieldPath  字段路径，例：a.b.c
     * @param string $sort       asc | desc
     * @param bool   $keepKeys   是否保留原始键名
     * @param bool   $strict     true=缺字段的放最后；false=缺字段的放最前
     * @return array
     */
    public static function toSortPath(
        array  $array,
        string $fieldPath = '',
        string $sort      = 'desc',
        bool   $keepKeys  = false,
        bool   $strict    = true
    ): array {

        if ($fieldPath === '') {
            return $array;
        }

        // 1. 把路径拆成数组
        $keys = explode('.', $fieldPath);

        // 2. 取出排序用的值
        $values = [];
        foreach ($array as $k => $item) {
            $v = $item;
            foreach ($keys as $key) {
                if (!is_array($v) || !array_key_exists($key, $v)) {
                    $v = null;          // 字段不存在
                    break;
                }
                $v = $v[$key];
            }
            $values[$k] = $v;
        }

        // 3. 排序
        $sortFlag = ($sort === 'asc') ? SORT_ASC : SORT_DESC;
        if ($keepKeys) {
            array_multisort($values, $sortFlag, SORT_REGULAR, $array);
        } else {
            array_multisort($values, $sortFlag, SORT_REGULAR, array_values($array));
            $array = array_values($array);
        }

        // 4. 把 null 值挪到末尾（或开头）——修复点
        $nulls = [];
        $valid = [];
        foreach ($array as $k => $item) {
            if ($values[$k] === null) {
                $nulls[$k] = $item;
            } else {
                $valid[$k] = $item;
            }
        }
        $array = $strict ? array_merge($valid, $nulls) : array_merge($nulls, $valid);

        // 如果不需要保留键名，再重置一次
        return $keepKeys ? $array : array_values($array);
    }

    /**
     * 一维数组去重
     * @param array $array 待处理数据
     * @param bool $delEmpty 是否去除空数组元素
     * @return array
     */
    public static function toOneUnique(array $array = [], bool $delEmpty = true): array
    {
        if ($delEmpty) {
            return array_keys(array_flip(array_filter($array)));
        } else {
            return array_keys(array_flip($array));
        }
    }

    /**
     * 二维数组去重
     * @param array $array 待处理数据
     * @param string $field 字段
     * @param bool $delIndexes 是否删除索引
     * @return array
     */
    public static function toTwoUnique(array $array = [], string $field = '', bool $delIndexes = true): array
    {
        if (empty($array) || empty($field)) return $array;
        $list = array_column($array, null, $field);
        return $delIndexes ? array_values($list) : $list;
    }

    /**
     * 以一维数组返回数据树
     * @param array $array
     * @param string $cKey 当前主键
     * @param string $pKey 父级主键
     * @param string $children 子级字段
     * @param string $mark 记号
     * @return array
     */
    public static function toTreeList(array $array = [], string $cKey = 'id', string $pKey = 'pid', string $children = 'children', string $mark = '└　'): array
    {
        $call = function (callable $call, array $array = [], array &$data = [], int $level = 0) use ($cKey, $pKey, $children, $mark) {
            foreach ($array as &$val) {
                $son = $val[$children] ?? [];
                unset($val[$children]);
                $val['mark'] = str_repeat($mark, $level);
                $data[] = $val;
                if (empty($son)) continue;
                $call($call, $son, $data, $level + 1);
            }
            return $data;
        };
        return $call($call, static::toTreeArray($array, $cKey, $pKey, $children));
    }

    /**
     * 以多维数组返回数据树
     * @param array $array
     * @param string $cKey 当前主键
     * @param string $pKey 父级主键
     * @param string $children 子级字段
     * @return array
     */
    public static function toTreeArray(array $array = [], string $cKey = 'id', string $pKey = 'pid', string $children = 'children'): array
    {
        [$tree, $tmp] = [[], array_column($array, null, $cKey)];
        foreach ($array as $value) {
            if (isset($value[$pKey]) && isset($tmp[$value[$pKey]])) {
                $tmp[$value[$pKey]][$children][] = &$tmp[$value[$cKey]];
            } else {
                $tree[] = &$tmp[$value[$cKey]];
            }
        }
        return $tree;
    }

    /**
     * 获取数据树子ID集合
     * @param array $array
     * @param null|int $value 起始有效ID值
     * @param string $cKey
     * @param string $pKey
     * @return array
     */
    public static function toChildrenIds(array $array = [], null|int $value = 0, string $cKey = 'id', string $pKey = 'pid'): array
    {
        $ids = [intval($value)];
        foreach ($array as $vo) {
            if (intval($vo[$pKey]) > 0 && intval($vo[$pKey]) === intval($value)) {
                $ids = array_merge($ids, static::toChildrenIds($array, intval($vo[$cKey]), $cKey, $pKey));
            }
        }
        return $ids;
    }
}
