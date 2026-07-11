<?php
declare(strict_types=1);

namespace cccms\model;

use cccms\Model;
use think\model\relation\HasMany;

class SysCrontab extends Model
{
    public function logs(): HasMany
    {
        return $this->hasMany(SysCrontabLog::class, 'crontab_id', 'id');
    }

    public function searchTitleAttr($query, $value): void
    {
        $query->where('title', 'like', '%' . $value . '%');
    }

    /**
     * 写入后(新增/修改/启停)自动刷新定时任务缓存
     */
    public static function onAfterWrite($model): void
    {
        self::refreshCrontab();
    }

    /**
     * 删除后自动刷新定时任务缓存
     */
    public static function onAfterDelete($model): void
    {
        self::refreshCrontab();
    }

    /**
     * 刷新定时任务缓存(增删改 sys_crontab 成功后自动调用)
     *
     * 异步执行 `php think cron --refresh`, 将最新任务列表写入缓存文件,
     * 使派发模式(宝塔每秒触发)读到最新列表, 同时避免每次都查库。
     * 采用后台执行(&), 不阻塞当前请求; 失败不影响主流程(增删改已成功)。
     */
    protected static function refreshCrontab(): void
    {
        try {
            $root = \app()->getRootPath();
            $php  = PHP_BINARY ?: 'php';
            $cmd  = sprintf('%s %s think cron --refresh > /dev/null 2>&1 &', $php, $root . 'think');
            @exec($cmd);
        } catch (\Throwable $e) {
            // 刷新失败不影响主流程
        }
    }
}
