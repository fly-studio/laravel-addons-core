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
		'success' => 'All success.',
		'error' => 'Something error, please retry.',
	],
	'server' => [
		'error_param' => 'Your URL\'s parameters were lost or invalid.',
		'error_referrer' => 'Your referrer [:referrer] is forbidden.',
		'error_server' => 'Server Error, please retry later.',
		'error_database' => 'Database error, please retry later.',
	],
	'validation' => [
		'csrf_invalid' => 'maybe you stay too long, please save your data.(copy the `content` to other tool, eg. notepad), then refresh this page, and retry.',
	],
	'auth' => [
		'success_login' => 'Login success, redirect to the page that you last visited.',
		'success_logout' => 'Logout success, redirect to the page that you last visited',
		'permission_forbidden' => 'You have no permission to visit this page，if you wanna switch a super user, <a href="'.url('auth').'" target="_self">Click here</a>.',
		'failure_login' => 'Username or Password is invalid.',
		'unlogin' => 'No login, if you wanna login, <a href="'.url('auth').'" target="_self">Click here</a>.',
		'unauthorized' => 'API Authorization Error, You must add the correctly HTTP Header "Authorization" to your request.',
	],
	'document' => [
		'not_exists' => 'the document that your visited is not exists.',
		'owner_deny' => 'You can not edit or view others document.',
		'model_not_exists' => 'The database [:model] have no data from ID [:id] <br /> :file line :line！',
	],
];
