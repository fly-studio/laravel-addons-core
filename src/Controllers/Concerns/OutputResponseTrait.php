<?php

namespace Addons\Core\Controllers\Concerns;

use BadMethodCallException;
use Addons\Core\Http\OutputResponseFactory;
use Addons\Core\Exceptions\OutputResponseException;

trait OutputResponseTrait {

	protected $outputTable = [
		'error_param' => 'server.error_param',
		'success_login' => 'auth.success_login',
		'success_logout' => 'auth.success_logout',
		'failure_login' => 'auth.failure_login',
		'failure_notexists' => 'document.not_exists',
		'failure_owner' => 'document.owner_deny',
	];

	public function __call($method, $parameters)
	{
		list($result) = explode('_', $method);
		//$this->api($data, $encrypted = false);
		//$this->office($data);
		if (in_array($result, ['api', 'office']))
		{
			return app(OutputResponseFactory::class)->make($result, ...$parameters);
		}
		// $this->success($message_name = null, $tipType = true, $data = [], $showData = true);
		// $this->failure,notice,warning($message_name = null, $tipType = false, $data = [], $showData = false);
		// $this->error_param($tipType = false, $data = [], $showData = false);
		// $this->success_login($tipType = true, $data = [], $showData = true);
		else if (in_array($result, ['error', 'failure', 'success', 'notice', 'warning']))
		{
			//将message_name入栈
			if ($method != $result)
				array_unshift($parameters, isset($this->outputTable[$method]) ? $this->outputTable[$method] : $method);

			//抛出成功或失败
			$response = app(OutputResponseFactory::class)->make($result, ...$parameters)->disableUser($this->disableUser);
			throw new OutputResponseException($response);
		}

		throw new BadMethodCallException("Method [{$method}] does not exist.");
	}
}
