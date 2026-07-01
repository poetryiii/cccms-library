<?php
declare (strict_types=1);

return [
    'name|规则描述' => 'require|length:1,100',
    'name.require' => '%s 不能为空',
    'name.length' => '%s 长度不能超过100个字符',

    'table_name|目标表' => 'require|length:1,100',
    'table_name.require' => '%s 不能为空',

    'field|目标字段' => 'require|length:1,100',
    'field.require' => '%s 不能为空',

    'rule_type|规则类型' => 'require|in:hidden,readonly,mask_show,condition',
    'rule_type.require' => '%s 不能为空',
    'rule_type.in' => '请选择正确的 %s',

    'rule_operator|条件操作符' => 'length:0,30',
    'rule_operator.length' => '%s 长度不能超过30个字符',

    'rule_value|条件值' => 'length:0,500',
    'rule_value.length' => '%s 长度不能超过500个字符',

    'status|状态' => 'require|in:0,1',
    'status.require' => '%s 不能为空',
    'status.in' => '请选择正确的 %s',
];
