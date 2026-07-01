<?php

declare(strict_types=1);

use think\facade\Route;

if ($this->app->http->getName() === 'index') {
    // 附件路由（<code> 仅匹配 32 位 hex，避免误匹配 /admin/file/index 等路径）
    Route::rule('file/:code', 'file/file')->name('file')->pattern(['code' => '[a-f0-9]{32}']);
}
