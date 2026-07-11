<?php
declare(strict_types=1);

return [
    // JWT签名密钥（生产环境请修改为高强度的随机字符串）
    // 此密钥独立于数据库密码，避免密钥泄露时影响数据库安全
    'key' => 'cccms_jwt_secret_' . md5(__DIR__ . date('Ymd')),
    // 签名算法
    'alg' => 'HS256',
    // Token签发者
    'iss' => 'cccms',
    // Token受众
    'aud' => 'cccms_admin',
];
