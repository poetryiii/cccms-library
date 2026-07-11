### 介绍

cccms-library 是基于 ThinkPHP 封装的一套基础类库（当前依赖 `topthink/framework ^8.0`），与 `poetry/cccms-app` 配合构成 CCCMS 应用框架。

本包通过 Composer 的 `extra.think.services` 注入 `cccms\Library` 服务，在应用启动时自动加载包内与项目内的扩展配置、验证规则、全局中间件，并内置定时任务统一调度器。

## 代码仓库

本项目为 MIT 协议开源项目，安装使用或二次开发不受约束，欢迎 fork 项目。

部分代码来自互联网，若有异议可以联系作者进行删除。

* Gitee 仓库地址：https://gitee.com/svipchao/cccms-library
* Github 仓库地址：https://github.com/svipchao/cccms-library

## 功能特性

* **配置/验证自动加载**：`cccms\Library` 服务在 `HttpRun`（HTTP 请求）时，扫描并合并加载
  `vendor/poetry/cccms-library/src/cccms/{config,validate}` 与项目 `cccms/{config,validate}`
  下的扩展文件，包内配置与项目配置同名时项目优先。
* **CLI 配置兼容**：由于 `HttpRun` 事件在命令行（`php think`）下不触发，服务在 `register()`
  阶段对 CLI 模式单独加载 `cccms/config/console.php`，保证命令行指令在 CLI 下正常注册。
* **数据库查询对象替换**：通过 `cccms\Query` 接管默认查询对象，提供字段缓存等扩展能力。
* **基础服务基类**：`cccms\Service`（构造需 `App` 实例）、`cccms\Model`、`cccms\Storage` 等。
* **通用业务服务**：`cccms\services\` 下提供 `ConfigService`（数据库配置读取）、
  `NodeService`、`UploadService`、`CaptchaService`、`BaseService` 等。
* **全局中间件**：`cccms\support\middleware\` 提供 `Cors`、`MultiApp`、`Log` 三个内置中间件。
* **URL 处理**：`cccms\support\Url` 接管 `think\route\Url`，统一路由 URL 生成。
* **定时任务调度器**：内置 `cron` 指令（见下文），以 `sys_crontab` 表为唯一数据源，
  支持缓存任务列表、按 `rule`（Cron 表达式）到点派发、按任务 ID 非阻塞锁防叠加、
  执行日志（`sys_crontab_log`）与增删改自动刷新缓存。

## 目录结构（节选）

```
src/
├── Library.php                      # 服务类，注册配置/验证/中间件加载
├── Service.php                      # 服务基类（构造需 App）
├── Model.php / Query.php           # 模型与查询对象
├── Storage.php / Base.php          # 存储与基础类
├── common.php                      # 全局辅助函数（如 _xss_safe）
├── cccms/
│   ├── config/                      # 包内扩展配置（app/cccms/cors/jwt/session/console...）
│   ├── validate/                    # 包内扩展验证规则
│   └── views/                       # 包内视图
├── services/                       # 通用业务服务
├── support/                         # Url、中间件等支撑组件
└── model/                          # SysUser、SysCrontab 等模型
```

## 定时任务使用方式

定时任务调度器是一个**纯调度工具**：本包不内置任何业务代码，仅负责「读 `sys_crontab` 表 →
异步派发 → 按任务加锁防叠加」。任务的真实逻辑由 `sys_crontab.command` 字段指向外部
（业务命令或 `Class@method`），**新增/删除/修改任务 = 对 `sys_crontab` 表增删改记录**，无需改动本包代码。

### 1. 架构

```
宝塔计划任务(shell 脚本, 每分钟触发, 一行不变)
   │  php think cron                 ← 派发模式
   ▼
cron 命令(读 sys_crontab 表 status=1, 优先命中文件缓存)
   │  逐个用 rule(Cron表达式)判断"当前是否到点"(5段分钟级/6段秒级), 到点才派发(按粒度去重)
   │  exec("php think cron --task=<id> >log 2>&1 &")  ← 异步非阻塞
   ├─────────────────────────────────────┐
   ▼                                      ▼
子进程A(--task=1)                  子进程B(--task=2)
   │ 按任务ID加非阻塞锁                    │ 按任务ID加非阻塞锁
   ▼                                      ▼
执行 command 字段                   执行 command 字段
   │ 写 sys_crontab_log + 更新 last_time   │ 写 sys_crontab_log + 更新 last_time
```

* **派发模式**（宝塔触发，不带 `--task`）：读任务列表 → 逐个用 `rule`（Cron 表达式，5 段分钟级 / 6 段秒级）
  判断当前是否到点，**到点的任务才** `exec(... &)` 异步派发 → 父进程立即返回（秒退，不阻塞宝塔）。
  同一任务同一周期只派发一次（按 `rule` 粒度去重：秒级按秒、分钟级按分钟，兼容宝塔秒级触发）。
  单次任务（`type=1`）若已执行过则跳过。
* **执行模式**（被派发，带 `--task=<id>`）：按任务 ID 加非阻塞文件锁 → 读取该任务 `command`
  字段并动态执行 → 写入 `sys_crontab_log` 执行日志、更新 `sys_crontab.last_time`；
  上次未跑完则跳过本次（防「多次触发叠加」）。
* **刷新缓存**（带 `--refresh`）：回源查 `sys_crontab` 写缓存文件，供派发模式读取。

> **调度粒度支持分钟级与秒级**：`rule` 兼容两种写法——
>  - **5 段（分钟级）**：`分 时 日 月 周`，支持 `* / , -` 与步长（如 `*/5 * * * *` 每 5 分钟、`0 3 * * *` 每天 03:00）。
>  - **6 段（秒级）**：`秒 分 时 日 月 周`，首段为秒（0-59），如 `*/10 * * * * *` 每 10 秒、`0 * * * * *` 每分钟整点秒。
>  分钟级任务宝塔配「每分钟」触发即可；**秒级任务需把宝塔计划任务改为「每秒」触发**（否则秒字段永远停在整分钟那 1 秒），去重会自动按秒。

### 2. 命令说明

| 命令 | 作用 |
|---|---|
| `php think cron` | 派发模式：读缓存中的任务列表，按 `rule` 到点的任务才异步派发 |
| `php think cron --task=<任务ID>` | 执行模式：运行指定 ID 的任务（由派发模式内部调用，会写执行日志） |
| `php think cron --refresh` | 刷新任务列表缓存（增删改定时任务后调用，一般由模型事件自动触发） |

命令类位置：`poetry/cccms-app/src/admin/command/Cron.php`（命名空间 `app\admin\command\Cron`），
注册于 `cccms-library/src/cccms/config/console.php`。

### 3. 在 `sys_crontab` 注册任务

向 `sys_crontab` 表插入启用（`status=1`）且带 `rule`（Cron 表达式）的任务记录即可，调度器自动识别：

```sql
-- 注意: type 默认 1(单次), 循环任务需显式写 type=2
-- rule 为标准 5 段 Cron: 分 时 日 月 周; 下例为每分钟执行
INSERT INTO sys_crontab (title, command, rule, type, status)
VALUES ('巨量token续期', 'php think oceanengine:renew-token', '* * * * *', 2, 1);
```

> **`rule` 必填且决定是否到点**：`* * * * *` 每分钟；`*/5 * * * *` 每 5 分钟；
> `0 3 * * *` 每天 03:00；`30 0 * * *` 每天 00:30；`0 */3 * * *` 每 3 小时。
> 秒级示例：`*/10 * * * * *` 每 10 秒；`0,30 * * * * *` 每分钟第 0/30 秒；`5 * * * * *` 每分钟第 5 秒。
> 派发模式只会派发「当前匹配 `rule`」的任务，`rule` 为空或不合法的任务不会被执行。

`command` 字段支持两种格式：

* **格式一：`Class@method`**（同进程直接调用，锁覆盖最完整）
  ```sql
  INSERT INTO sys_crontab (title, command, rule, params, type, status)
  VALUES ('巨量token续期', 'app\oceanengine\service\JlAccountService@renewAll', '0 * * * *', '', 2, 1);
  ```
  调度器执行 `(new $class)->$method($params)`，类需可被自动加载。
* **格式二：think 命令**（`php think xxx` 或简写 `xxx`，由框架执行）
  ```sql
  INSERT INTO sys_crontab (title, command, rule, type, status)
  VALUES ('巨量token续期', 'php think oceanengine:renew-token', '0 * * * *', 2, 1);
  ```

#### 任务类型 `type`（单次 / 循环）

`sys_crontab.type` 控制调度器派发策略：

* `type=2`（**循环**）：按 `rule` 周期性重复执行，每次到点都派发（受去重约束）。
* `type=1`（**单次**）：只执行**一次**。派发模式会跳过「已派发过（单次标记文件）」的单次任务；
  执行模式完成（无论成功或失败）后会自动将其 `status` 置为 `0`（禁用），此后不再派发。

> **⚠️ `type` 字段默认值是 `1`（单次）**：若用 SQL 直接插入、且未显式写 `type`，任务会变成「单次」，
> 跑一次后自动禁用。需要**周期性重复执行**的任务务必显式指定 `type=2`（见上方示例）。

> 单次任务适合「一次性数据修复 / 一次性通知」等场景。若需手动重跑，可在后台用「▶ 执行」按钮
> （`admin/crontab/execute`）手动触发——手动执行**不会**改变 `type`/`status`，也不会触发自动禁用。
> 后台「添加/修改」表单中 `type` 字段即对应「单次 / 循环」单选。

```sql
-- 单次任务: 只跑一次, 跑完自动禁用(status=0)
INSERT INTO sys_crontab (title, command, rule, type, status)
VALUES ('一次性数据修复', 'php think fix:something', '* * * * *', 1, 1);
```

### 4. 宝塔计划任务配置

宝塔 → 计划任务 → 任务类型「Shell 脚本」，**执行周期选「每分钟」**（调度粒度为分钟，
由 `rule` 决定各任务何时执行，故这里固定每分钟触发一次即可，**脚本一行不变**）：

```bash
#!/bin/bash
PHP=/www/server/php/82/bin/php            # 改为你的 PHP 实际路径
CRON=/www/wwwroot/zgl.qq.com/cccms/think   # 改为你的 think 入口实际路径
$PHP $CRON cron
```

不同频率的任务无需多个脚本，统一由本脚本每分钟触发，`sys_crontab` 表中每条记录的 `rule`
决定实际何时执行、`status` 决定是否启用。

### 5. 缓存与刷新（避免每秒查库）

派发模式默认读取文件缓存 `sys_get_temp_dir()/oe_crontab_cache.json`，**平时不查数据库**；
仅在以下情况回源查库：缓存文件不存在，或主动执行 `--refresh`。

**自动刷新（增删改成功后）**：`cccms\model\SysCrontab` 已在模型事件中挂载自动刷新——
新增 / 修改 / 启停触发 `onAfterWrite`，删除触发 `onAfterDelete`，二者均调用模型内部的
`refreshCrontab()`（异步后台执行 `php think cron --refresh`）。刷新逻辑与事件同处
`SysCrontab` 模型，职责内聚，不再依赖 `cccms\Library`。

因此通过后台对 `sys_crontab` 做任何增 / 删 / 改后，**缓存会自动更新**，无需手动调用命令。
该逻辑位于 `cccms-library` 内，与业务代码解耦，且可覆盖所有写入入口（不仅限管理后台）。

**手动刷新（兜底）**：如缓存异常或跨进程未触发，仍可直接执行：

```bash
php think cron --refresh
```

### 6. 防叠加锁

每个任务按 ID 生成独立文件锁 `sys_get_temp_dir()/oe_lock_<md5('cron_'.$id)>.lock`，
使用 `flock(LOCK_EX | LOCK_NB)` 非阻塞加锁：

* 同一任务上次未跑完 → 本次直接跳过，避免「多次触发叠加」导致进程堆积。
* 不同任务各用各的锁，互不阻塞，可真正并行。

### 7. 常见排查

* **宝塔日志只显示 `Successful`、没有 `派发任务#X`**：说明本次没有到点任务被派发。逐项排查：
  1. 是否有启用任务：执行 `php think cron --refresh`，看输出「共 N 个启用任务」，N=0 说明
     `sys_crontab` 无 `status=1` 记录（或缓存是旧的空 `[]`，`--refresh` 会强制回源刷新）。
  2. `rule` 是否到点：5 段按分钟、6 段按秒匹配 `rule`，例如 `0 3 * * *` 只在每天 03:00 那一分钟派发，
     `*/10 * * * * *` 每 10 秒派发一次。想立即验证可临时设为 `* * * * *`（每分钟）或 `* * * * * *`（每秒）。
  3. 宝塔周期与 `rule` 粒度要匹配：分钟级任务配「每分钟」、秒级任务（6 段 rule）需配「每秒」。
  4. 看执行日志：后台「定时任务 → 日志」或直接查 `sys_crontab_log` 表，确认是否已执行、结果如何。
  5. 看派发子进程日志：`sys_get_temp_dir()/oe_cron_<任务id>.log`（Linux 一般为 `/tmp`）。
* **命令行 `php think cron` 报数据库连接错误**：确认 `.env`/`database` 配置正确、数据库可连。

### 8. 注意事项

* **宝塔秒级频率**：若单任务执行耗时可能超过触发间隔，锁机制已防止叠加堆积，但请合理评估任务耗时。
* **vendor 文件风险**：以下文件位于 `vendor/` 下，执行 `composer update poetry/cccms-library`
  或 `poetry/cccms-app` 可能被覆盖，建议纳入版本管理或 patch 备份：
  - `cccms-library/src/cccms/config/console.php`（命令注册）
  - `cccms-library/src/Library.php`（CLI 配置加载）
  - `cccms-library/src/model/SysCrontab.php`（`onAfterWrite` / `onAfterDelete` 事件 + `refreshCrontab()` 自动刷新）
  - `cccms-app/src/admin/command/Cron.php`（调度器实现）
* **CLI 配置加载**：`cccms\Library` 已对 CLI 模式单独加载 `console.php`，升级 `cccms-library`
  后若该逻辑被上游改动，需重新补上，否则 `cron` 命令在命令行下无法注册。
