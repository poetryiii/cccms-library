<?php

declare(strict_types=1);

namespace cccms;

use think\{App, Request, Service};
use think\facade\Route;
use cccms\support\Url;
use cccms\services\BaseService;
use cccms\support\middleware\{Cors, MultiApp, Log};

class Library extends Service
{
    /**
     * 静态应用实例
     * @var App
     */
    public static App $sapp;

    // 启动服务
    public function boot(): void
    {
        // 静态应用赋值
        static::$sapp = $this->app;

        // 绑定URL类
        $this->app->bind(['think\route\Url' => Url::class]);
    }

    /**
     * 注册服务
     */
    public function register(): void
    {
        // CLI(php think) 模式下 HttpRun 事件不触发, 不会执行下方 setConfig(),
        // 导致 vendor/poetry/cccms-library/src/cccms/config 下的 console.php 等配置无法被加载。
        // 此处单独为 CLI 合并多来源的 console.commands, 使命令注册在命令行下同样生效:
        //   - 项目级 cccms/config/console.php
        //   - 应用级 cccms/cccms/config/console.php(CLI 下 HttpRun 不触发, 此处补加载)
        //   - 包自带 vendor/poetry/cccms-library/src/cccms/config/console.php(如 cron 调度器)
        // 采用"合并 + 去重"而非"覆盖", 避免 vendor 包配置把项目/应用层定义的指令冲掉。
        if ($this->app->runningInConsole()) {
            $rootPath = $this->app->getRootPath();
            $sources  = [
                $rootPath . 'config/console.php',
                $rootPath . 'cccms/config/console.php',
                $rootPath . 'vendor/poetry/cccms-library/src/cccms/config/console.php',
            ];
            $commands = [];
            foreach ($sources as $file) {
                if (is_file($file)) {
                    $cfg = include $file;
                    if (is_array($cfg) && !empty($cfg['commands']) && is_array($cfg['commands'])) {
                        $commands = array_merge($commands, $cfg['commands']);
                    }
                }
            }
            // 以值去重, 防止多来源重复注册同一指令
            $commands = array_values(array_unique($commands));
            $console  = $this->app->config->get('console', []);
            $console['commands'] = $commands;
            $this->app->config->set($console, 'console');
        }

        $this->app->event->listen('HttpRun', function (Request $request) {
            // 配置默认输入过滤
            $request->filter([function ($value) {
                return is_string($value) ? _xss_safe($value) : $value;
            }]);
            // 设置扩展配置文件
            $this->setConfig();
            // 设置扩展验证文件
            $this->setValidate();
            // 设置数据库指定查询对象
            $this->setDatabaseQuery();
            // 设置全局中间件
            $this->app->middleware->import(array_merge_recursive(
                [Cors::class, MultiApp::class, Log::class],
                $this->app->config->get('cccms.middleware', [])
            ));
        });
    }

    /**
     * 设置扩展验证文件
     * @return void
     */
    private function setValidate(): void
    {
        $rootPath = $this->app->getRootPath();
        $lang = $this->app->lang->getLangSet();
        $toScanFileArray = array_merge(
            BaseService::instance()->scanDirArray($rootPath . 'vendor/poetry/cccms-library/src/cccms/validate/' . $lang . '*'),
            BaseService::instance()->scanDirArray($rootPath . 'cccms/validate/' . $lang . '*')
        );
        foreach ($toScanFileArray as $file) {
            $this->app->config->load($file, 'validate_' . pathinfo($file, PATHINFO_FILENAME));
        }
    }

    /**
     * 设置扩展配置文件
     * @return void
     */
    private function setConfig(): void
    {
        $rootPath = $this->app->getRootPath();
        $toScanFileArray = array_merge(
            BaseService::instance()->scanDirArray($rootPath . 'vendor/poetry/cccms-library/src/cccms/config/*'),
            BaseService::instance()->scanDirArray($rootPath . 'cccms/config/*')
        );
        foreach ($toScanFileArray as $file) {
            $this->app->config->load($file, pathinfo($file, PATHINFO_FILENAME));
        }
    }

    /**
     * 设置数据库指定查询对象
     * @return void
     */
    private function setDatabaseQuery(): void
    {
        // 设置数据库指定查询对象
        $database = $this->app->config->get('database', []);
        $database['connections'][$database['default']]['query'] = '\\cccms\\Query';
        $database['connections'][$database['default']]['fields_cache'] = true;
        $this->app->config->set($database, 'database');
    }
}
