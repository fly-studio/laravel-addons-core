<?php
return [

    'default' => [
        'success' => '操作成功。',
        'error' => '服务器发生错误。',
    ],
    'server' => [
        'error_param' => '您传递的参数错误，请检查您的来路是否正确。',
        'error_referrer' => '您的请求来源[:referrer]不在许可范围内。',
        'error_server' => '服务器内部错误，请稍后再试。',
        'error_database' => '服务器数据库出现错误，请稍后再试。',
    ],
    'validation' => [
        'csrf_invalid' => '您停留时间过长，请刷新后重新提交！',
    ],
    'auth' => [
        'success_login' => '登录成功，即将跳转到刚才的页面。',
        'success_logout' => '登出成功，即将跳转到刚才的页面。',
        'permission_forbidden' => '您的权限不够，无法执行该操作，或无法访问本页面',
        'failure_login' => '账号或密码错误。',
        'unlogin' => '您尚未登录，无法访问本页面。',
        'unauthorized' => '您调用的API校验错误，需要在HTTP请求头中添加正确的Authorization头信息。',
    ],
    'document' => [
        'not_exists' => '您要查找的资料不存在。',
        'owner_deny' => '您无法查看或修改他人的资料。',
        'model_not_exists' => '无法在数据库[:model]中查询到数据[:id] '.PHP_EOL.' :file line :line。',
    ],
];
