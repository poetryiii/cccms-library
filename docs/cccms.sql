-- ================================================================
-- CCCMS 完整数据库结构
-- 版本：v2.0
-- 生成日期：2025-06-24
-- 兼容：MySQL 8.0+
-- 字符集：utf8mb4 / utf8mb4_general_ci
-- ================================================================

-- ================================================================
-- 一、RBAC 权限体系（5对象模型）
-- ================================================================

-- 1.1 用户表
CREATE TABLE `sys_user`
(
    `id`          int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `nickname`    varchar(32)  NOT NULL DEFAULT '' COMMENT '昵称',
    `username`    varchar(32)  NOT NULL DEFAULT '' COMMENT '用户名',
    `password`    varchar(255) NOT NULL DEFAULT '' COMMENT '密码(bcrypt)',
    `phone`       varchar(11)  NOT NULL DEFAULT '' COMMENT '手机号',
    `avatar`      varchar(255) NOT NULL DEFAULT '' COMMENT '头像',
    `tags`        varchar(255) NOT NULL DEFAULT '' COMMENT '用户标签',
    `status`      tinyint      NOT NULL DEFAULT 1 COMMENT '状态【0:禁用,1:正常】',
    `delete_time` datetime     NOT NULL DEFAULT '1900-01-01 00:00:00' COMMENT '删除时间',
    `create_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `update_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `idx_nickname` (`nickname`) USING BTREE,
    INDEX `idx_tags` (`tags`) USING BTREE,
    INDEX `idx_status` (`status`) USING BTREE,
    UNIQUE KEY `idx_username` (`username`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '用户表';

-- admin 初始密码: admin（bcrypt加密，首次登录后建议立即修改）
INSERT INTO `sys_user` (`id`, `nickname`, `username`, `password`, `status`)
VALUES (1, '超级管理员', 'admin', '$2b$10$/Aqq5kHtVK06pEB.RjU0mO/hXFxlyH3YPcM1t2E37zdChRM4Sim4G', 1);

-- 1.2 用户-部门关联表
CREATE TABLE `sys_user_dept`
(
    `user_id`    int unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
    `dept_id`    int unsigned NOT NULL DEFAULT 0 COMMENT '部门ID',
    `auth_range` tinyint(4)   NOT NULL DEFAULT 0 COMMENT '权限范围【0:本人,1:本部门,2:本部门及下属部门】',
    INDEX `idx_dept_id` (`dept_id`) USING BTREE,
    UNIQUE INDEX `uk_user_dept` (`user_id`, `dept_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '用户部门关联表';

-- 1.3 用户-岗位关联表
CREATE TABLE `sys_user_post`
(
    `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
    `post_id` int unsigned NOT NULL DEFAULT 0 COMMENT '岗位ID',
    INDEX `idx_post_id` (`post_id`) USING BTREE,
    UNIQUE INDEX `uk_user_post` (`user_id`, `post_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '用户岗位关联表';

-- 1.4 用户-角色直连关联表
CREATE TABLE `sys_user_role`
(
    `user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
    `role_id` int unsigned NOT NULL DEFAULT 0 COMMENT '角色ID',
    INDEX `idx_role_id` (`role_id`) USING BTREE,
    UNIQUE INDEX `uk_user_role` (`user_id`, `role_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '用户角色关联表';

-- 2.1 部门表
CREATE TABLE `sys_dept`
(
    `id`          int unsigned  NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `dept_id`     int unsigned  NOT NULL DEFAULT 0 COMMENT '父级ID',
    `dept_path`   varchar(2048) NOT NULL DEFAULT '' COMMENT '父级ID集合',
    `dept_name`   varchar(32)   NOT NULL DEFAULT '' COMMENT '部门名称',
    `dept_desc`   varchar(255)  NOT NULL DEFAULT '' COMMENT '部门备注',
    `status`      tinyint       NOT NULL DEFAULT 1 COMMENT '状态【0:禁用,1:正常】',
    `delete_time` datetime      NOT NULL DEFAULT '1900-01-01 00:00:00' COMMENT '删除时间',
    `create_time` datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `update_time` datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '部门表';

-- 2.2 部门-角色关联表
CREATE TABLE `sys_dept_role`
(
    `dept_id` int unsigned NOT NULL DEFAULT 0 COMMENT '部门ID',
    `role_id` int unsigned NOT NULL DEFAULT 0 COMMENT '角色ID',
    INDEX `idx_role_id` (`role_id`) USING BTREE,
    UNIQUE INDEX `uk_dept_role` (`dept_id`, `role_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '部门角色关联表';

-- 3.1 岗位表
CREATE TABLE `sys_post`
(
    `id`          int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `post_code`   varchar(100) NOT NULL DEFAULT '' COMMENT '岗位编码',
    `post_name`   varchar(100) NOT NULL DEFAULT '' COMMENT '岗位名称',
    `sort`        int          NOT NULL DEFAULT 0 COMMENT '排序',
    `status`      tinyint      NOT NULL DEFAULT 1 COMMENT '状态【0:禁用,1:正常】',
    `remark`      varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `update_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `idx_sort` (`sort`) USING BTREE,
    INDEX `idx_status` (`status`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '岗位表';

-- 4.1 角色表
CREATE TABLE `sys_role`
(
    `id`          int unsigned  NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `role_id`     int unsigned  NOT NULL DEFAULT 0 COMMENT '父级ID',
    `role_path`   varchar(2048) NOT NULL DEFAULT '' COMMENT '父级ID集合',
    `role_name`   varchar(32)   NOT NULL DEFAULT '' COMMENT '角色名称',
    `role_desc`   varchar(255)  NOT NULL DEFAULT '' COMMENT '角色备注',
    `status`      tinyint       NOT NULL DEFAULT 1 COMMENT '状态【0:禁用,1:正常】',
    `delete_time` datetime      NOT NULL DEFAULT '1900-01-01 00:00:00' COMMENT '删除时间',
    `create_time` datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `update_time` datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '角色表';

-- 4.2 角色-权限节点关联表
CREATE TABLE `sys_role_node`
(
    `role_id` int unsigned NOT NULL DEFAULT 0 COMMENT '角色ID',
    `node`    varchar(128) NOT NULL DEFAULT '' COMMENT '权限节点',
    UNIQUE INDEX `uk_role_node` (`role_id`, `node`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '角色权限节点关联表';

-- 5.1 菜单表
CREATE TABLE `sys_menu`
(
    `id`          int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `parent_id`   int unsigned NOT NULL DEFAULT 0 COMMENT '父级ID',
    `name`        varchar(32)  NOT NULL DEFAULT '' COMMENT '菜单名称',
    `icon`        varchar(32)  NOT NULL DEFAULT '' COMMENT '菜单图标',
    `url`         varchar(255) NOT NULL DEFAULT '#' COMMENT '链接',
    `node`        varchar(255) NOT NULL DEFAULT '#' COMMENT '节点',
    `layout_name` varchar(32)  NOT NULL DEFAULT 'layouts' COMMENT '菜单布局名称',
    `target`      varchar(32)  NOT NULL DEFAULT '_self' COMMENT '链接打开方式',
    `sort`        int          NOT NULL DEFAULT 0 COMMENT '排序',
    `status`      tinyint      NOT NULL DEFAULT 1 COMMENT '状态【0:禁用,1:正常】',
    `delete_time` datetime     NOT NULL DEFAULT '1900-01-01 00:00:00' COMMENT '删除时间',
    `create_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `update_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '菜单表';

INSERT INTO `sys_menu` (`id`, `parent_id`, `name`, `icon`, `url`, `node`, `layout_name`, `target`, `sort`, `status`)
VALUES (1, 0, '控制台', 'ri-home-line', 'admin/index/index', 'admin/index/index', 'default', '_self', 0, 1),
       (2, 0, '权限配置', 'ri-shield-check-line', '#', '#', 'default', '_self', 0, 1),
       (3, 2, '部门管理', '', 'admin/dept/index', 'admin/dept/index', 'default', '_self', 0, 1),
       (4, 2, '角色管理', '', 'admin/role/index', 'admin/role/index', 'default', '_self', 0, 1),
       (5, 2, '用户管理', '', 'admin/user/index', 'admin/user/index', 'default', '_self', 0, 1),
       (6, 2, '岗位管理', '', 'admin/post/index', 'admin/post/index', 'default', '_self', 0, 1),
       (7, 2, '数据权限', '', 'admin/data_auth/index', 'admin/data_auth/index', 'default', '_self', 0, 1),
       (8, 0, '系统配置', 'ri-settings-line', '#', '#', 'default', '_self', 0, 1),
       (9, 8, '系统设置', '', 'admin/config/index', 'admin/config/index', 'default', '_self', 0, 1),
       (10, 8, '菜单管理', '', 'admin/menu/index', 'admin/menu/index', 'default', '_self', 0, 1),
       (11, 8, '附件管理', '', 'admin/file/index', 'admin/file/index', 'default', '_self', 0, 1),
       (12, 8, '日志管理', '', 'admin/log/index', 'admin/log/index', 'default', '_self', 0, 1),
       (13, 8, '字典管理', '', 'admin/dict/index', 'admin/dict/index', 'default', '_self', 0, 1),
       (14, 8, '定时任务', '', 'admin/crontab/index', 'admin/crontab/index', 'default', '_self', 0, 1);

-- ================================================================
-- 二、系统配置管理
-- ================================================================

-- 6.1 配置分类表
CREATE TABLE `sys_config_cate`
(
    `id`          int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `cate_name`   varchar(32)  NOT NULL DEFAULT '' COMMENT '配置标识',
    `cate_desc`   varchar(255) NOT NULL DEFAULT '' COMMENT '配置描述',
    `sort`        int          NOT NULL DEFAULT 0 COMMENT '排序',
    `create_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `update_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '配置分类表';

INSERT INTO `sys_config_cate` (`id`, `cate_name`, `cate_desc`)
VALUES (1, 'site', '网站配置'),
       (2, 'captcha', '验证码配置'),
       (3, 'log', '日志配置'),
       (4, 'storage', '上传配置'),
       (5, 'watermark', '水印配置');

-- 6.2 配置详情表
CREATE TABLE `sys_config`
(
    `id`          int unsigned  NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `cate_name`   varchar(32)   NOT NULL DEFAULT '' COMMENT '配置标识',
    `name`        varchar(100)  NOT NULL DEFAULT '' COMMENT '名称',
    `label`       varchar(100)  NOT NULL DEFAULT '' COMMENT '标签',
    `value`       varchar(1024) NOT NULL DEFAULT '' COMMENT '值',
    `configure`   text          NOT NULL COMMENT '配置(JSON)',
    `sort`        int           NOT NULL DEFAULT 0 COMMENT '排序',
    `create_time` datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `update_time` datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '配置详情表';

INSERT INTO `sys_config` (`id`, `cate_name`, `name`, `label`, `value`, `configure`)
VALUES (1, 'site', 'siteName', '网站名称', '诗无尽头i', '{"type":"input","placeholder":"请输入网站名称","default":"默认站点","description":"请输入网站名称"}'),
       (2, 'site', 'siteIcp', '备案号', '豫ICP备93093369号', '{"type":"input","placeholder":"请输入网站备案号","default":"豫ICP备93093369号","description":"请输入网站备案号"}'),
       (3, 'captcha', 'open', '启用验证码', 1, '{"type":"switch","default":1,"description":"是否启用验证码","options":{"checked":1,"unchecked":0}}'),
       (4, 'captcha', 'math', '验证码类型', 1, '{"type":"select","placeholder":"请选择验证码类型","default":0,"description":"请选择验证码类型","options":[{"value":"0","label":"普通验证码"},{"value":"1","label":"算数验证码"}]}'),
       (5, 'captcha', 'length', '验证码位数', 4, '{"type":"input-number","placeholder":"请输入验证码位数，建议4位或5位","default":4,"description":"请输入验证码位数，建议4位或5位"}'),
       (6, 'captcha', 'fontSize', '验证码字体大小', 22, '{"type":"input-number","placeholder":"请输入验证码字体大小(px)","default":22,"description":"请输入验证码字体大小(px)"}'),
       (7, 'captcha', 'matchCase', '是否区分大小写', 0, '{"type":"switch","default":0,"description":"是否区分大小写","options":{"checked":1,"unchecked":0}}'),
       (8, 'captcha', 'useCurve', '混淆曲线', 1, '{"type":"switch","default":1,"description":"是否添加混淆曲线","options":{"checked":1,"unchecked":0}}'),
       (9, 'captcha', 'useNoise', '杂点', 1, '{"type":"switch","default":1,"description":"是否添加杂点","options":{"checked":1,"unchecked":0}}'),
       (10, 'log', 'logClose', '日志状态', 1, '{"type":"switch","default":1,"description":"日志状态","options":{"checked":1,"unchecked":0}}'),
       (11, 'log', 'logMethods', '监控类型', 'put,post,delete', '{"type":"multiple-select","placeholder":"请选择需要监控的类型","default":["POST","PUT","DELETE"],"description":"参考：https://www.runoob.com/http/http-methods.html","options":[{"value":"GET","label":"GET"},{"value":"POST","label":"POST"},{"value":"PUT","label":"PUT"},{"value":"DELETE","label":"DELETE"},{"value":"HEAD","label":"HEAD"},{"value":"CONNECT","label":"CONNECT"},{"value":"OPTIONS","label":"OPTIONS"},{"value":"TRACE","label":"TRACE"},{"value":"PATCH","label":"PATCH"}]}'),
       (12, 'log', 'logNoParams', '不记录的参数', 'v,page,limit,field,order,encode', '{"type":"textarea","placeholder":"请输入不需要记录的参数名","default":"page,limit","description":"每个参数使用英文逗号隔开，例如：page,limit"}'),
       (13, 'storage', 'diskType', '磁盘类型', 'local', '{"type":"select","placeholder":"请选择磁盘类型","default":"local","description":"请选择磁盘类型","options":[{"value":"local","label":"本地存储"},{"value":"alioss","label":"阿里云"},{"value":"qiniu","label":"七牛云"},{"value":"txoss","label":"腾讯云"},{"value":"uposs","label":"又拍云"}]}'),
       (14, 'storage', 'uploadExt', '上传文件类型', 'jpg,png,gif,mp4,mp3', '{"type":"textarea","placeholder":"请输入上传文件支持的后缀名","default":"page,limit","description":"每个后缀名使用英文逗号隔开，例如：jpg,png,gif"}'),
       (15, 'storage', 'uploadSize', '上传文件大小限制', 20, '{"type":"input-number","placeholder":"请输入上传文件大小限制","default":20,"description":"请输入上传文件限制(MB)"}'),
       (16, 'storage', 'compressLevel', '图片压缩等级', 8, '{"type":"input-number","placeholder":"请输入上传文件压缩等级","default":8,"description":"请输入上传文件压缩等级(1-10)"}'),
       (17, 'watermark', 'open', '是否启用水印', 1, '{"type":"switch","placeholder":"是否启用水印","options":{"checked":1,"unchecked":0}}'),
       (18, 'storage', 'ossAccessKeyId', 'OSS AccessKey', '', '{"type":"input","placeholder":"请输入OSS AccessKeyId","description":"阿里云/腾讯云OSS的AccessKey","condition":{"field":"diskType","not":"local"}}'),
       (19, 'storage', 'ossAccessKeySecret', 'OSS SecretKey', '', '{"type":"password","placeholder":"请输入OSS AccessKeySecret","description":"OSS密钥","condition":{"field":"diskType","not":"local"}}'),
       (20, 'storage', 'ossEndpoint', 'OSS Endpoint', '', '{"type":"input","placeholder":"如 oss-cn-hangzhou.aliyuncs.com","description":"OSS访问域名","condition":{"field":"diskType","not":"local"}}'),
       (21, 'storage', 'ossBucket', 'OSS Bucket', '', '{"type":"input","placeholder":"请输入Bucket名称","description":"OSS存储桶名称","condition":{"field":"diskType","not":"local"}}'),
       (22, 'storage', 'ossDomain', 'OSS 自定义域名', '', '{"type":"input","placeholder":"如 https://cdn.example.com（可选）","description":"OSS自定义访问域名，留空则使用Endpoint","condition":{"field":"diskType","not":"local"}}');

-- ================================================================
-- 三、文件资源管理
-- ================================================================

CREATE TABLE `sys_file`
(
    `id`           int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `user_id`      int unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
    `file_size`    int unsigned NOT NULL DEFAULT 0 COMMENT '文件大小',
    `file_name`    varchar(255) NOT NULL DEFAULT '' COMMENT '文件名',
    `file_desc`    varchar(255) NOT NULL DEFAULT '' COMMENT '文件描述',
    `file_ext`     varchar(20)  NOT NULL DEFAULT '' COMMENT '文件后缀',
    `file_mime`    varchar(100) NOT NULL DEFAULT '' COMMENT '文件类型',
    `file_path`    varchar(255) NOT NULL DEFAULT '' COMMENT '文件路径',
    `file_code`    char(32)     NOT NULL DEFAULT '' COMMENT '文件唯一码',
    `file_md5`     char(32)     NOT NULL DEFAULT '' COMMENT 'md5值',
    `file_sha1`    char(64)     NOT NULL DEFAULT '' COMMENT 'sha1值',
    `extract_code` varchar(20)  NOT NULL DEFAULT '' COMMENT '提取码',
    `tags`         json         NOT NULL COMMENT '标签(JSON数组)',
    `status`       tinyint      NOT NULL DEFAULT 1 COMMENT '状态【0:禁用,1:正常】',
    `create_time`  datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `update_time`  datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `idx_file_code` (`file_code`),
    INDEX `idx_user_id` (`user_id`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '文件详情表';

-- ================================================================
-- 四、系统日志管理
-- ================================================================

-- 8.1 操作日志主表
CREATE TABLE `sys_log`
(
    `id`          int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `user_id`     int unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
    `name`        varchar(255) NOT NULL DEFAULT '' COMMENT '行为名称',
    `node`        varchar(255) NOT NULL DEFAULT '' COMMENT '操作节点',
    `req_ip`      varchar(45)  NOT NULL DEFAULT '' COMMENT '请求IP',
    `req_method`  varchar(7)   NOT NULL DEFAULT '' COMMENT '请求类型',
    `req_ua`      varchar(255) NOT NULL DEFAULT '' COMMENT 'User-Agent',
    `create_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `idx_user_id` (`user_id`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '操作日志表';

-- 8.2 操作日志详情表
CREATE TABLE `sys_log_info`
(
    `log_id`     int unsigned NOT NULL COMMENT '日志ID',
    `req_params` longtext     NOT NULL COMMENT '请求参数',
    `upd_params` longtext     NOT NULL COMMENT '修改参数',
    `req_result` longtext     NOT NULL COMMENT '请求结果',
    UNIQUE KEY `idx_log_id` (`log_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci COMMENT = '操作日志详情表';

-- ================================================================
-- 五、数据字典管理
-- ================================================================

-- 9.1 字典类型表
CREATE TABLE `sys_dict_type`
(
    `id`          int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `dict_name`   varchar(100) NOT NULL DEFAULT '' COMMENT '字典名称',
    `dict_type`   varchar(100) NOT NULL DEFAULT '' COMMENT '字典类型标识',
    `status`      tinyint      NOT NULL DEFAULT 1 COMMENT '状态【0:禁用,1:正常】',
    `remark`      varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
    `create_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `update_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    UNIQUE KEY `uk_dict_type` (`dict_type`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '字典类型表';

-- 9.2 字典数据表
CREATE TABLE `sys_dict_data`
(
    `id`          int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `type_id`     int unsigned NOT NULL DEFAULT 0 COMMENT '字典类型ID',
    `label`       varchar(100) NOT NULL DEFAULT '' COMMENT '字典标签',
    `value`       varchar(100) NOT NULL DEFAULT '' COMMENT '字典键值',
    `sort`        int          NOT NULL DEFAULT 0 COMMENT '排序',
    `status`      tinyint      NOT NULL DEFAULT 1 COMMENT '状态【0:禁用,1:正常】',
    `remark`      varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
    `css_class`   varchar(100) NOT NULL DEFAULT '' COMMENT '样式属性',
    `list_class`  varchar(100) NOT NULL DEFAULT '' COMMENT '表格回显样式',
    `is_default`  tinyint      NOT NULL DEFAULT 0 COMMENT '是否默认',
    `create_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `update_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `idx_type_id` (`type_id`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '字典数据表';

-- ================================================================
-- 六、定时任务管理
-- ================================================================

-- 11.1 定时任务配置表
CREATE TABLE `sys_crontab`
(
    `id`          int unsigned  NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `title`       varchar(100)  NOT NULL DEFAULT '' COMMENT '任务标题',
    `command`     varchar(255)  NOT NULL DEFAULT '' COMMENT '执行命令/类方法',
    `rule`        varchar(100)  NOT NULL DEFAULT '' COMMENT 'Cron表达式',
    `params`      varchar(500)  NOT NULL DEFAULT '' COMMENT '任务参数',
    `status`      tinyint       NOT NULL DEFAULT 1 COMMENT '状态【0:停用,1:启用】',
    `type`        tinyint       NOT NULL DEFAULT 1 COMMENT '类型【1:单次,2:循环】',
    `max_retry`   tinyint       NOT NULL DEFAULT 0 COMMENT '失败重试次数',
    `remark`      varchar(500)  NOT NULL DEFAULT '' COMMENT '备注',
    `last_time`   datetime      NOT NULL DEFAULT '1900-01-01 00:00:00' COMMENT '上次执行时间',
    `create_time` datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `update_time` datetime      NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `idx_status` (`status`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '定时任务表';

-- 11.2 定时任务执行日志表
CREATE TABLE `sys_crontab_log`
(
    `id`          int unsigned   NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `crontab_id`  int unsigned   NOT NULL DEFAULT 0 COMMENT '任务ID',
    `status`      tinyint        NOT NULL DEFAULT 1 COMMENT '执行状态【0:失败,1:成功】',
    `exec_time`   decimal(10,4)  NOT NULL DEFAULT 0 COMMENT '执行耗时(秒)',
    `result`      text           NOT NULL COMMENT '执行结果',
    `create_time` datetime       NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '执行时间',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `idx_crontab_id` (`crontab_id`) USING BTREE,
    INDEX `idx_create_time` (`create_time`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '定时任务执行日志表';

-- ================================================================
-- 七、数据权限管理
-- ================================================================

-- 12.1 数据权限规则表
CREATE TABLE `sys_data_auth`
(
    `id`            int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `name`          varchar(100) NOT NULL DEFAULT '' COMMENT '规则描述',
    `table_name`    varchar(100) NOT NULL COMMENT '目标表',
    `field`         varchar(100) NOT NULL COMMENT '目标字段',
    `rule_type`     varchar(20)  NOT NULL DEFAULT 'hidden' COMMENT '规则类型【hidden:隐藏, readonly:只读, mask_show:掩码, condition:条件筛选】',
    `rule_operator` varchar(30)  DEFAULT NULL COMMENT '条件操作符(仅condition类型, 如 eq/ne/gt/lt/in/all_like/between 等)',
    `rule_value`    varchar(500) DEFAULT NULL COMMENT '条件值(仅condition类型, 如 10 / 2026-01-01 / 1,2,3)',
    `role_id`       int unsigned DEFAULT NULL COMMENT '绑定角色ID',
    `dept_id`       int unsigned DEFAULT NULL COMMENT '绑定部门ID',
    `post_id`       int unsigned DEFAULT NULL COMMENT '绑定岗位ID',
    `user_id`       int unsigned DEFAULT NULL COMMENT '绑定用户ID',
    `priority`      int          NOT NULL DEFAULT 300 COMMENT '优先级(越小越高，内置: user=0,post=100,dept=200,role=300)',
    `status`        tinyint      NOT NULL DEFAULT 1 COMMENT '状态【0:禁用,1:启用】',
    `create_time`   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `update_time`   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`) USING BTREE,
    INDEX `idx_table` (`table_name`) USING BTREE,
    INDEX `idx_role` (`role_id`) USING BTREE,
    INDEX `idx_user` (`user_id`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT = '数据权限规则表';

-- ================================================================
-- 表关系总结
-- ================================================================
-- sys_user ──┬── sys_user_dept ──── sys_dept ──── sys_dept_role ──── sys_role ──── sys_role_node
--            ├── sys_user_post ─── sys_post
--            └── sys_user_role ─── sys_role ──── sys_role_node
--
-- sys_menu ── 注解扫描生成权限节点 → sys_role_node
--
-- sys_data_auth ── 角色/部门/岗位/用户 四维度绑定 → 控制字段隐藏/只读/掩码 + 行级条件筛选
--
-- 权限链路：
--   用户 → 部门 → 部门角色 → 角色节点 → 权限节点
--   用户 → 直连角色 ──────────→ 角色节点 → 权限节点
--   用户 → 岗位（身份标签，不参与权限计算）
--
-- 数据权限链路（5层过滤）：
--   L2 数据归属: sys_user_dept → user_id IN(部门成员)
--   L3 部门范围: dept_id IN(用户部门)           ← scopeUserDataAuth / applyDeptAuth
--   L4 字段权限: hidden|readonly|mask_show       ← sys_data_auth(字段级)
--   L5 行级条件: condition + rule_operator/rule_value → AND 叠加过滤
-- ================================================================
