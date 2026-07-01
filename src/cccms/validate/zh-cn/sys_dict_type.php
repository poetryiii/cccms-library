<?php
declare(strict_types=1);

return [
    'dict_name|字典名称' => 'require|length:1,100',
    'dict_name.length' => '字典名称长度需在1-100之间',
    'dict_type|字典标识' => 'require|length:1,100|alphaDash',
    'dict_type.length' => '字典标识长度需在1-100之间',
    'dict_type.alphaDash' => '字典标识只允许字母、数字、下划线和破折号',
];
