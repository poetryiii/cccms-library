<?php
declare(strict_types=1);

return [
    'type_id|字典类型' => 'require|integer',
    'label|字典标签' => 'require|length:1,100',
    'label.length' => '字典标签长度需在1-100之间',
    'value|字典键值' => 'require|length:1,100',
    'value.length' => '字典键值长度需在1-100之间',
];
