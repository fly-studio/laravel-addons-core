<?php
namespace Addons\Core\Controllers;

use Auth;
use BadMethodCallException;
use Addons\Core\Http\OutputResponseFactory;
use Addons\Core\Exceptions\OutputResponseException;

trait OutputTrait {

	protected $viewData = [];
	protected $addons = true;
	protected $outputTable = [
		'error_param' => 'server.error_param',
		'success_login' => 'auth.success_login',
		'success_logout' => 'auth.success_logout',
		'failure_login' => 'auth.failure_login',
		'failure_notexists' => 'document.not_exists',
		'failure_owner' => 'document.owner_deny',
	];

	public function __set($key, $value)
	{
		$this->viewData[$key] = $value;
	}

	public function __get($key)
	{
		return $this->viewData[$key];
	}

	public function __isset($key)
	{
		return isset($this->viewData[$key]);
	}

	public function __unset($key)
	{
		unset($this->viewData[$key]);
	}

	protected function subtitle($title, $url = NULL, $target = '_self')
	{
		$title = trans($title);
		$titles = config('settings.subtitles', []);
		config(['settings.subtitles' => array_merge($titles, [compact('title', 'url', 'target')])]);
	}

	protected function view($filename, $data = [])
	{
		if ($this->addons) $this->viewData['_user'] = Auth::user();
		return view($filename, $data)->with($this->viewData);
	}

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
			$response = app(OutputResponseFactory::class)->make($result, ...$parameters);
			throw new OutputResponseException($response);
		}

        throw new BadMethodCallException("Method [{$method}] does not exist.");
	}

}