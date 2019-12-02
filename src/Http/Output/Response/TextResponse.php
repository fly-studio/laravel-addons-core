<?php

namespace Addons\Core\Http\Output\Response;

use Lang, Auth;
use JsonSerializable;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Addons\Core\File\Mimes;
use Illuminate\Http\Response;
use Addons\Core\Tools\Output;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Addons\Core\Contracts\Protobufable;
use Illuminate\Contracts\Support\Jsonable;
use Addons\Core\Http\Output\ActionFactory;
use Illuminate\Contracts\Support\Arrayable;
use Symfony\Component\HttpFoundation\Request;

use Addons\Core\Structs\Protobuf\Output as OutputProto;
use Addons\Core\Structs\Protobuf\OutputMessage as MessageProto;
use Addons\Core\Structs\Protobuf\OutputTipType as TipTypeProto;

class TextResponse extends Response implements Protobufable, Jsonable, Arrayable, JsonSerializable {

	protected $request = null;
	protected $data = null;
	protected $of = 'auto';
	protected $message = null;
	protected $action = null;
	protected $uid = null;
	protected $code = 0;

	public function data($data)
	{
		$data = json_decode(json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR), true); //turn Object to Array

		$this->data = $data;
		return $this;
	}

	public function message(?string $message_name, array $transData = null)
	{
		if (empty($message_name))
		{
			$this->message = null;
			return $this;
		}

		if (Lang::has($message_name))
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

	public function rawMessage($message)
	{
		$this->message = $message;
		return $this;
	}

	public function action(...$action)
	{
		$this->action = (new ActionFactory())->make(...$action);
		return $this;
	}

	public function code(int $code)
	{
		$this->code = $code;

		/*if ($code >= 100 && $code < 600)
			$this->setStatusCode($code, $text);*/

		return $this;
	}

	public function request(?Request $request)
	{
		$this->request = $request;
		return $this;
	}

	public function uid(?int $uid)
	{
		$this->uid = $uid;
		return $this;
	}

	public function of(?string $of)
	{
		$this->of = $of;
		return $this;
	}

	public function getRequest()
	{
		return is_null($this->request) ? app('request') : $this->request;
	}

	public function getOf()
	{
		if ($this->of == 'auto')
		{
			$request = $this->getRequest();
			$route = $request->route();
			$of = $request->query('of', null);

			if (!in_array($of, ['txt', 'text', 'json', 'xml', 'yaml', 'html']))
				$of = $request->expectsJson() || (!empty($route) && in_array('api', $route->gatherMiddleware())) ? 'json' : 'html';

			return $of;
		}

		return $this->formatter;
	}

	public function getData()
	{
		return $this->data;
	}

	public function getCode()
	{
		return $this->code;
	}

	public function getMessage()
	{
		if (empty($this->message))
		{
			$code = $this->getStatusCode();

			if ($code != Response::HTTP_OK)
			{
				return Lang::has('exception.http.'.$code) ? trans('exception.http.'.$code) : trans('core::common.default.error');
			} else {
				return trans('core::common.default.sucess');
			}
		}

		return $this->message;
	}

	public function getAction()
	{
		return $this->action;
	}

	public function getOutputData()
	{
		return $this->toArray();
	}

	public function prepare(Request $request)
	{
		$data = $this->getOutputData();

		$charset = config('app.charset');
		$of = $this->getOf();
		$response = null;
		$original = $this->original;

		switch ($of) {
			case 'xml':
			case 'txt':
			case 'text':
			case 'yaml':
			case 'html': //text
				$content = $of != 'html' ? Output::$of($data) : view('tips', ['_data' => $data]);

				$response = $this->setContent($content)
					->header('Content-Type', Mimes::getInstance()->mime_by_ext($of).'; charset='.$charset);

				break;
			default: //其余全部为json

				$jsonResponse = (new JsonResponse($data, $this->getStatusCode(), [], JSON_PARTIAL_OUTPUT_ON_ERROR))
					->withCallback($request->query('callback')); //pajax 必须是GET请求，以免和POST字段冲突

				$response = $this->setContent($jsonResponse->getContent())
					->withHeaders($jsonResponse->headers->all())
					->header('Content-Type', 'application/json');

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

	public function toProtobuf(): \Google\Protobuf\Internal\Message
	{
		$data = $this->toArray();

		$o = new OutputProto();
		$o->setResult($data['result']);
		$o->setCode($data['status_code']);
		!empty($data['uid']) && $o->setUid($data['uid']);
		$o->setDebug($data['debug']);

		if (!empty($data['message']))
		{
			$m = new MessageProto();
			if (is_array($data['message']))
			{
				$m->setTitle($data['message']['title'] ?? null);
				$m->setContent($data['message']['content'] ?? null);
			} else if (is_string($message))
				$m->setContent($data['message'] ?? null);
			$o->setMessage($m);
		}

		if (!empty($data['tipType']))
		{
			$t = new TipTypeProto();
			!empty($data['tipType']['type']) && $t->setType($data['tipType']['type']);
			!empty($data['tipType']['timeout']) && $t->setTimeout($data['tipType']['timeout']);
			!empty($data['tipType']['url']) && $t->setUrl($data['tipType']['url']);
			$o->setTipType($t);
		}
		$d = !is_array($data['data']) ? $data['data'] : json_encode($data['data'], JSON_PARTIAL_OUTPUT_ON_ERROR);
		!is_null($d) && $o->setData($d);

		$o->setTime($data['time']);
		$o->setDuration($data['duration']);
		$o->setBody($data['body']);

		return $o;
	}

	public function toArray()
	{
		$result = [
			'code' => $this->getCode(),
			'message' => $this->getMessage(),
			'action' => $this->getAction(),
			'data' => $this->getData(),
			'uid' => $this->uid ? null : (Auth::check() ? Auth::user()->getKey() : null),
			'at' => microtime(true),
		];

		if (config('app.debug')) {
			$result += [
				'duration' => microtime(true) - LARAVEL_START,
				'body' => strval($this->original),
			];
		}

		return $result;
	}

	public function jsonSerialize()
	{
		return $this->toArray();
	}

	/**
	 * Convert the model instance to JSON.
	 *
	 * @param  int  $options
	 * @return string
	 *
	 * @throws \Illuminate\Database\Eloquent\JsonEncodingException
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->jsonSerialize(), $options);
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
		if (empty($replace)) {
			return $line;
		}
		$replace = $this->sortReplacements($replace);
		$replace = Arr::dot($replace);

		foreach ($replace as $key => $value) {
			if (is_array($value)) continue;
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
