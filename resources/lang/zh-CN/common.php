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
		'success' => [
			'title' => '操作成功',
			'content' => '您的操作已经成功！',
		],
		'error' => [
			'title' => '操作错误',
			'content' => '您的操作错误，请重试！',
		],
		'failure' => [
			'title' => '操作失败',
			'content' => '您的操作失败，请重试！',
		],
		'warning' => [
			'title' => '操作警告',
			'content' => '您的操作不正确，请重试！',
		],
		'notice' => [
			'title' => '提示',
			'content' => '请阅读并理解提示信息！',
		],
	],
	'server' => [
		'error_param' => [
			'title' => '参数错误',
			'content' => '您传递的参数错误，请检查您的来路是否正确！',
		],
		'error_referrer' => [
			'title' => '请求来源错误',
			'content' => '您的请求来源[:referrer]不在许可范围内！',
		],
		'error_server' => [
			'title' => '服务器内部错误',
			'content' => '服务器内部错误，请稍后再试！',
		],
		'error_database' => [
			'title' => '数据库错误',
			'content' => '服务器数据库出现错误，请稍后再试！',
		],
	],
	'validation' => [
		'csrf_invalid' => [
			'title' => 'CSRF检测无法通过',
			'content' => '您可能停留页面时间过长，请保存数据后，刷新当前页面后再重试！',
		],
	],
	'auth' => [
		'success_login' => [
			'title' => '登录成功',
			'content' => '即将跳转到刚才的页面！',
		],
		'success_logout' => [
			'title' => '登出成功',
			'content' => '即将跳转到刚才的页面！',
		],
		'permission_forbidden' => [
			'title' => '权限不够',
			'content' => '您的权限不够，无法执行该操作，或无法访问本页面，切换用户请<a href="'.url('auth').'" target="_self">点击这里</a>！',
		],
		'failure_login' => [
			'title' => '登录失败',
			'content' => '账号或密码错误！',
		],
		'unlogin' => [
			'title' => '您尚未登录',
			'content' => '您尚未登录，无法访问本页面，登录请<a href="'.url('auth').'" target="_self">点击这里</a>！',
		],
		'unAuthorization' => [
			'title' => 'API校验错误',
			'content' => '您调用的API校验错误，需要在HTTP请求头中添加正确的：Authorization头信息。',
		],
	],
	'document' => [
		'not_exists' => [
			'title' => '资料不存在',
			'content' => '您要查找的资料不存在！',
		],
		'owner_deny' => [
			'title' => '越权',
			'content' => '您无法查看或修改他人的资料！',
		],
		'model_not_exists' => [
			'title' => '数据不存在',
			'content' => '无法在数据库[:model]中查询到数据[:id] <br /> :file line :line！',
		],
	],
];
