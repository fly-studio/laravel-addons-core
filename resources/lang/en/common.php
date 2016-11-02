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
			'title' => 'Done!',
			'content' => 'all Done,enjoy it!',
		],
		'error' => [
			'title' => 'Error!',
			'content' => 'something errors.retry,please!',
		],
		'failure' => [
			'title' => 'Failure!',
			'content' => 'something mistakes.retry,please!',
		],
		'success_login' => [
			'title' => 'Login successful!',
			'content' => 'logon,back to the referrer!',
		],
		'error_param' => [
			'title' => 'Paramters Error!',
			'content' => 'please visit the website with broswer!',
		],
		'error_referrer' => [
			'title' => 'Referrer Error!',
			'content' => 'your referrer:[:referrer] is not allow!',
		],
		'error_server' => [
			'title' => 'Server Error!',
			'content' => 'server error,please retry later!',
		],
		'error_database' => [
			'title' => 'Database Error!',
			'content' => 'database error,please retry later！',
		],
		'failure_validate' => [
			'title' => 'POST Failure!',
			'content' => '<ul class="post_faiure">:messages</ul>',
			'list' => '<li>:message</li>'.PHP_EOL,
		],
		'failure_auth' => [
			'title' => 'Permission Failure',
			'content' => 'permission failure,<a href="'.url('auth').'" target="_self">Click it</a> to change the user!',
		],
		'failure_login' => [
			'title' => 'Login Failure!',
			'content' => 'username or password is invail!',
		],
		'failure_unlogin' => [
			'title' => 'No Logon',
			'content' => 'you are not logon,<a href="'.url('auth').'" target="_self">Click it</a> to login!',
		],
		'failure_edit_other' => [
			'title' => '修改越权',
			'content' => '您要修改的资料不属于您，请勿越权！',
		],
		'failure_view_other' => [
			'title' => '查看越权',
			'content' => '您无法查看别人的资料！',
		],
		'failure_noexist' => [
			'title' => '资料不存在',
			'content' => '您要查找的资料不存在！',
		],
	],
];
