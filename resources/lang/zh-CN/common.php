<?php
return [

	/*
	|--------------------------------------------------------------------------
	| Common Language Lines
	|--------------------------------------------------------------------------
	|
	|
	|
	|
	|
	*/

	'default' => [
		'success' => '操作成功！',
		'error' => '服务器发生错误！',
	],
	'server' => [
		'error_param' => '您传递的参数错误，请检查您的来路是否正确！',
		'error_referrer' => '您的请求来源[:referrer]不在许可范围内！',
		'error_server' => '服务器内部错误，请稍后再试！',
		'error_database' => '服务器数据库出现错误，请稍后再试！',
	],
	'validation' => [
		'csrf_invalid' => '您可能停留页面时间过长，请保存数据后，刷新当前页面后再重试！',
	],
	'auth' => [
		'success_login' => '登录成功，即将跳转到刚才的页面！',
		'success_logout' => '登出成功，即将跳转到刚才的页面！',
		'permission_forbidden' => '您的权限不够，无法执行该操作，或无法访问本页面，切换用户请<a href="'.url('auth').'" target="_self">点击这里</a>！',
		'failure_login' => '账号或密码错误！',
		'unlogin' => '您尚未登录，无法访问本页面，登录请<a href="'.url('auth').'" target="_self">点击这里</a>！',
		'unAuthorization' => '您调用的API校验错误，需要在HTTP请求头中添加正确的：Authorization头信息。',
	],
	'document' => [
		'not_exists' => '您要查找的资料不存在！',
		'owner_deny' => '您无法查看或修改他人的资料！',
		'model_not_exists' => '无法在数据库[:model]中查询到数据[:id] <br /> :file line :line！',
	],
];
