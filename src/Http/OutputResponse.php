<?php
namespace Addons\Core\Http;

use Symfony\Component\HttpFoundation\Request;
use Addons\Core\Tools\Output;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Addons\Core\File\Mimes;
use Lang, Auth;

class OutputResponse extends Response {

	protected $data = null;
	protected $formatter = 'auto';
	protected $message = null;
	protected $url = false;
	protected $result = 'success';

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
			$request = app('request');
			$route = $request->route();
			$of = $request->input('of', null);
			if (!in_array($of, ['txt', 'text', 'json', 'xml', 'yaml', 'html']))
				$of = $request->expectsJson() || in_array('api', $route->gatherMiddleware()) ? 'json' : 'html';
			return $of;
		}
		return $this->formatter;
	}

	public function setUrl($url)
	{
		$this->url = is_string($url) ? url($url) : $url;
		return $this;
	}

	public function getUrl()
	{
		return $this->url;
	}

	public function setData($data)
	{
		$data = json_decode(json_encode($data), true); //turn Object to Array
		//
		$this->data = $data;
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
		$message = is_array($message_name) ? $message_name : trans(Lang::has($message_name) || !Lang::has('core::common.'.$message_name) ? $message_name : 'core::common.'.$message_name);

		if (!empty($transDatatra))
		{
			$translator = app('translator');
			if (is_array($message))
				foreach ($message as &$v)
					$v = call_class_method($translator, 'makeReplacements', $v, $transData);
			else 
				$message = call_class_method($translator, 'makeReplacements' , $transData);
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
		return empty($this->message) ? trans('core::common.default.' . $this->getResult() ) : $this->message;
	}

	public function getOutputData()
	{
		$result = [
			'result' => $this->getResult(),
			'status_code' => $this->getStatusCode(),
			'uid' => Auth::check() ? Auth::user()->getKey() : null,
			'debug' => config('app.debug'),
			'message' => $this->getMessage(),
			'url' => $this->getUrl(),
			'data' => $this->getData(),
			'time' => time(),
			'duration' => microtime(true) - LARAVEL_START,
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
				$response = (new JsonResponse($data))->withCallback($callback)->setStatusCode($this->getStatusCode());
				break;
		}
		return $response;
	}
}