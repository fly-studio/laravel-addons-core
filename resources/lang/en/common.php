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
			'title' => 'Done',
			'content' => 'All success.',
		],
		'error' => [
			'title' => 'Error',
			'content' => 'Something error(maybe server error), please retry.',
		],
		'failure' => [
			'title' => 'Failure',
			'content' => 'Something failure, please retry.',
		],
		'warning' => [
			'title' => 'Warning',
			'content' => 'Warning, some operation were danger.',
		],
		'notice' => [
			'title' => 'Notice',
			'content' => 'Please read the tip seriously.',
		],
	],
	'server' => [
		'error_param' => [
			'title' => 'Parameter Error',
			'content' => 'Your URL\'s parameters were lost or invalid.',
		],
		'error_referrer' => [
			'title' => 'Referrer Error',
			'content' => 'Your referrer [:referrer] is forbidden.',
		],
		'error_server' => [
			'title' => 'Server Error',
			'content' => 'Server Error, please retry later.',
		],
		'error_database' => [
			'title' => 'Database Error',
			'content' => 'Database error, please retry later.',
		],
	],
	'validation' => [
		'csrf_invalid' => [
			'title' => 'CSRF Invalid',
			'content' => 'maybe you stay too long, please save your data.(copy the `content` to other tool, eg. notepad), then refresh this page, and retry.',
		],
	],
	'auth' => [
		'success_login' => [
			'title' => 'Login Success',
			'content' => 'Login success, redirect to the page that you last visited.',
		],
		'success_logout' => [
			'title' => 'Logout Success',
			'content' => 'Logout success, redirect to the page that you last visited',
		],
		'permission_forbidden' => [
			'title' => 'Permission Forbidden',
			'content' => 'You have no permission to visit this page，if you wanna switch a super user, <a href="'.url('auth').'" target="_self">Click here</a>.',
		],
		'failure_login' => [
			'title' => 'Login Failure',
			'content' => 'Username or Password is invalid.',
		],
		'unlogin' => [
			'title' => 'No Login',
			'content' => 'No login, if you wanna login, <a href="'.url('auth').'" target="_self">Click here</a>.',
		],
		'unAuthorization' => [
			'title' => 'API Authorization Error',
			'content' => 'API Authorization Error, You must add the correctly HTTP Header "Authorization" to your request.',
		],
	],
	'document' => [
		'not_exists' => [
			'title' => 'No Document Exists',
			'content' => 'the document that your visited is not exists.',
		],
		'owner_deny' => [
			'title' => 'Cross-User Failure',
			'content' => 'You can not edit or view others document.',
		],
		'model_not_exists' => [
			'title' => 'No Record Exists ',
			'content' => 'The database [:model] have no data from ID [:id] <br /> :file line :line！',
		],
	],
];
