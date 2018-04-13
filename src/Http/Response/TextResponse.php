<?php

namespace Addons\Core\Http\Response;

use Lang, Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Addons\Core\File\Mimes;
use Illuminate\Http\Response;
use Addons\Core\Tools\Output;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Addons\Core\Http\Output\TipTypeManager;
use Symfony\Component\HttpFoundation\Request;
use Addons\Core\Contracts\Http\Output\TipType;

class TextResponse extends Response {

	protected $request = null;
	protected $data = null;
	protected $formatter = 'auto';
	protected $message = null;
	protected $tipType = null;
	protected $result = 'success';
	protected $outputRaw = false;
	protected $disableUser = false;

	public function setRequest($request)
	{
		$this->request = $request;
		return $this;
	}

	public function getRequest()
	{
		return is_null($this->request) ? app('request') : $this->request;
	}

	public function disableUser($disabled)
	{
		$this->disableUser = $disabled;
		return $this;
	}

	public function setResult($result)
	{
		$this->result = $result;
		return $this;
	}

	public function getResult()
	{
		return $this->result;
	}

	public function setFormatter($formatter)
	{
		$this->formatter = $formatter;
		return $this;
	}

	public function getFormatter()
	{
		if ($this->formatter == 'auto')
		{
			$request = $this->getRequest();
			$route = $request->route();
			$of = $request->input('of', null);
			if (!in_array($of, ['txt', 'text', 'json', 'xml', 'yaml', 'html']))
				$of = $request->expectsJson() || (!empty($route) && in_array('api', $route->gatherMiddleware())) ? 'json' : 'html';
			return $of;
		}
		return $this->formatter;
	}

	public function setAutoTip($tip)
	{
		return $this->setTipType(app(TipTypeManager::class)->autoDriver($tip));
	}

	public function setTipType(TipType $tipType)
	{
		$this->tipType = $tipType;
		return $this;
	}

	public function getTipType()
	{
		//default tipType
		if (is_null($this->tipType))
		{
			switch ($this->getResult()) {
				case 'success':
					return app(TipTypeManager::class)->autoDriver(true);
				case 'failure':
				case 'error':
				case 'notice':
				case 'warning':
					return app(TipTypeManager::class)->autoDriver(false);
				case 'api':
				default:
					return app(TipTypeManager::class)->driver();
			}
		}
		return $this->tipType;
	}

	public function setData($data, $outputRaw = false)
	{
		$data = json_decode(json_encode($data), true); //turn Object to Array
		//
		$this->data = $data;
		$this->outputRaw = $outputRaw;
		return $this;
	}

	public function getData()
	{
		return $this->data;
	}

	public function setMessage($message_name, $transData = [])
	{
		if (empty($message_name))
		{
			$this->message = null;
			return $this;
		}
		if (is_array($message_name))
			$message = $message_name;
		else if (Lang::has($message_name))
			$message = trans($message_name);
		else if (strpos($message_name, '::') === false && Lang::has('core::common.'.$message_name))
			$message = trans('core::common.'.$message_name);
		else
			$message = $message_name;

		if (!empty($transData))
		{
			if (is_array($message))
			{
				foreach ($message as &$v)
					$v = $this->makeReplacements($v, $transData);
			}
			else
				$message = $this->makeReplacements($message, $transData);
		}
		$this->message = $message;
		return $this;
	}

	public function setRawMessage($message)
	{
		$this->message = $message;
		return $this;
	}

	public function getMessage()
	{
		$code = $this->getStatusCode();
		return empty($this->message) ? ($code != 200 && Lang::has('exception.http.'.$code) ? trans('exception.http.'.$code) : trans('core::common.default.' . $this->getResult())) : $this->message;
	}

	public function getOutputData()
	{
		$result = $this->outputRaw ? $this->getData() : [
			'result' => $this->getResult(),
			'status_code' => $this->getStatusCode(),
			'uid' => $this->disableUser ? null : (Auth::check() ? Auth::user()->getKey() : null),
			'debug' => config('app.debug'),
			'message' => $this->getMessage(),
			'tipType' => $this->getTipType(),
			'data' => $this->getData(),
			'time' => time(),
			'duration' => microtime(true) - LARAVEL_START,
			'body' => strval($this->original),
		];
		return $result;
	}

	public function prepare(Request $request)
	{
		$data = $this->getOutputData();
		$charset = config('app.charset');
		$callback = $request->query('callback'); //必须是GET请求，以免和POST字段冲突
		$of = $this->getFormatter();
		$response = null;
		$original = $this->original;
		switch ($of) {
			case 'xml':
			case 'txt':
			case 'text':
			case 'yaml':
			case 'html': //text
				$content = $of != 'html' ? Output::$of($data) : view('tips', ['_data' => $data]);
				$response = $this->setContent($content)->header('Content-Type', Mimes::getInstance()->mime_by_ext($of).'; charset='.$charset);
				break;
			default: //其余全部为json
				$jsonResponse = (new JsonResponse($data))->withCallback($callback)->setStatusCode($this->getStatusCode());
				$response = $this->setContent($jsonResponse->getContent())->withHeaders($jsonResponse->headers->all())->header('Content-Type', 'application/json');
				break;
		}
		$this->original = $original;
		return $response;
	}

	/**
	 * Sends HTTP headers and content.
	 *
	 * @return Response
	 */
	public function send()
	{
		//404的错误比较特殊，无法找到路由，并且不会执行prepare
		$this->prepare($this->getRequest());

		return parent::send();
	}

	/**
	 * Returns the Response as an HTTP string.
	 *
	 * The string representation of the Response is the same as the
	 * one that will be sent to the client only if the prepare() method
	 * has been called before.
	 *
	 * @return string The Response as an HTTP string
	 *
	 * @see prepare()
	 */
	public function __toString()
	{
		//404的错误比较特殊，无法找到路由，并且不会执行prepare
		$this->prepare($this->getRequest());
		return
			sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText)."\r\n".
			$this->headers."\r\n".
			$this->getContent();
	}

	/**
	 * Make the place-holder replacements on a line.
	 *
	 * @param  string  $line
	 * @param  array   $replace
	 * @return string
	 */
	protected function makeReplacements($line, array $replace)
	{
		$replace = $this->sortReplacements($replace);
		$replace = Arr::dot($replace);

		foreach ($replace as $key => $value) {
			$line = str_replace(
				[':'.$key, ':'.Str::upper($key), ':'.Str::ucfirst($key)],
				[$value, Str::upper($value), Str::ucfirst($value)],
				$line
			);
		}

		return $line;
	}

	/**
	 * Sort the replacements array.
	 *
	 * @param  array  $replace
	 * @return array
	 */
	protected function sortReplacements(array $replace)
	{
		return (new Collection($replace))->sortBy(function ($value, $key) {
			return mb_strlen($key) * -1;
		})->all();
	}
}