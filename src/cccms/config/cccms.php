<?php
declare(strict_types=1);

return [
    'appName' => [
        'admin' => '系统管理',
        'index' => '默认应用',
    ],
    'resultPath' => app()->getRootPath() . 'vendor/poetry/cccms-library/src/cccms/views/result.tpl',
    'middleware' => [
        'think\middleware\SessionInit'
    ],
    'user' => [
        // 用户类型
        'types' => [
            '后台用户',
            '前台会员'
        ]
    ],
    'storage' => [
        // 附件访问路由配置
        'routePath' => '/file/<code>'
    ],
    'crontab' => [
        // 定时任务动态执行白名单（格式: "完整类名@方法名"）
        'allow_commands' => [
            // 'app\\crontab\\Task@cleanLogs',
        ],
        // 命名空间白名单，允许该命名空间下所有类方法被动态执行
        'allow_namespaces' => [
            // 'app\\crontab\\',
        ],
    ]
];