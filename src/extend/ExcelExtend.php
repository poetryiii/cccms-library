<?php

declare(strict_types=1);

namespace cccms\extend;

/**
 * 导出 CSV 文件（纯 UTF-8 版，彻底解决乱码）
 */
class ExcelExtend
{
    /**
     * 设置 CSV 头部（纯 UTF-8，不转码）
     * @param string $name 导出文件名称（无需.csv后缀）
     * @param array $headers 表格头部(一维数组)
     */
    public static function header(string $name, array $headers): void
    {
        // 1. 文件名处理：只 URL 编码，不转 GBK
        $filename = $name . '.csv';
        // 兼容所有浏览器的文件名编码
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Edge') !== false) {
            $filename = urlencode($filename);
        }

        // 2. 设置 UTF-8 响应头（核心：不指定 GBK）
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        // 3. 打开输出流，写入 UTF-8 BOM 头（Excel 识别 UTF-8 的关键）
        $handle = fopen('php://output', 'w');
        // 写入 UTF-8 BOM 头（必须！）
        fwrite($handle, "\xEF\xBB\xBF");

        // 4. 表头直接输出（不转码！）
        fputcsv($handle, $headers);

        if (is_resource($handle)) {
            fclose($handle);
        }
    }

    /**
     * 写入 CSV 内容（纯 UTF-8，不转码）
     * @param array $list 数据列表(二维数组)
     * @param array $rules 数据字段规则(一维数组)
     */
    public static function body(array $list, array $rules): void
    {
        $handle = fopen('php://output', 'w');
        foreach ($list as $data) {
            $rows = [];
            foreach ($rules as $rule) {
                // 取值：只做类型转换，不转码！
                $value = static::parseKeyDotValue($data, $rule);
                // 确保是字符串，避免非字符串类型导致的问题
                $rows[] = is_scalar($value) ? (string)$value : '';
            }
            // 直接写入 UTF-8 内容
            fputcsv($handle, $rows);
        }
        if (is_resource($handle)) {
            fclose($handle);
        }
    }

    /**
     * 根据数组key查询(可带点规则)
     * @param array $data 数据
     * @param string $rule 规则，如: order.order_no
     * @return mixed
     */
    public static function parseKeyDotValue(array $data, string $rule): mixed
    {
        [$temp, $attr] = [$data, explode('.', trim($rule, '.'))];
        while ($key = array_shift($attr)) {
            $temp = $temp[$key] ?? '';
        }
        return $temp;
    }
}
