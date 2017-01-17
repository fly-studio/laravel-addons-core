<?php
namespace Addons\Core\Controllers;

use BadMethodCallException;
//use Illuminate\Http\Exception\HttpResponseException;
use Addons\Core\Exceptions\OutputResponseException;
use Addons\Core\Http\OutputResponse;
use Addons\Core\Http\ApiResponse;
use Addons\Core\Http\OfficeResponse;
use Auth, Lang;
trait OutputTrait {

	protected $viewData = [];
	protected $addons = true;
	protected $outputTable = [
		'error_param' => 'server.error_param',
		'success_login' => 'auth.success_login',
		'success_logout' => 'auth.success_logout',
		'failure_login' => 'auth.failure_login',
		'failure_noexists' => 'document.failure_noexist',
		'failure_owner' => 'document.failure_owner',
		'failure_post' => 'validation.failure_post',
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
		$titles = config('settings.subtitles', []);
		config(['settings.subtitles' => array_merge($titles, compact('title', 'url', 'target'))]);
	}

	protected function view($filename, $data = [])
	{
		if ($this->addons) $this->viewData['_user'] = Auth::user();
		return view($filename, $data)->with($this->viewData);
	}

	public function __call($method, $parameters)
	{
		list($type) = explode('_', $method);
		if (in_array($type, ['error', 'failure', 'api', 'export', 'success', 'notice', 'warning']))
		{
			if ($method == 'api')
			{
				list($data, $encryptd) = $parameters + [[], false];
				$response = new ApiResponse();
				return $response->setData($data, $encryptd);
			}
			else if ($method == 'export')
			{
				list($data) = $parameters + [[]];
				$response = new OfficeResponse();
				return $response->setData($data);
			}
			// $this->success($message_name = null, $url = true, $data = [], $showData = true);
			// $this->failure,notice,warning($message_name = null, $url = false, $data = [], $showData = false);
			// $this->error_param($url = false, $data = [], $showData = false);
			// $this->success_login($url = true, $data = [], $showData = true);
			else if ($method == $type || isset($this->outputTable[$method]))
			{
				if ($method != $type) array_unshift($parameters, Lang::has($this->outputTable[$method]) ? $this->outputTable[$method] : 'core::common.'.$this->outputTable[$method]);

				list($message_name, $url, $data, $showData) = $parameters + ($type == 'success' ? [null, true, [], true] : [null, false, [], false]);

				$response = new OutputResponse();
				$response->setResult($type)->setMessage($message_name, $data)->setUrl($url);
				if ($showData) $response->setData($data);

				if ($type != 'success')
					throw new OutputResponseException($response); // 如果failure 则直接抛出

				return $response;
			}
		}
        throw new BadMethodCallException("Method [{$method}] does not exist.");
	}

}