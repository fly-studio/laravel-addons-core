<?php
namespace Addons\Core\Controllers;

use Addons\Core\Tools\Output;
use Addons\Core\Tools\OutputEncrypt;
use Illuminate\Http\Exception\HttpResponseException;
use Addons\Core\File\Mimes;
use Illuminate\Support\Facades\Lang;
use Illuminate\Http\JsonResponse;

trait OutputTrait {

	protected $viewData = [];

	public function __set($key, $value)
	{
		$this->viewData[$key] = $value;
		//view()->share($key, $value);
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

	protected function view($filename, $data = [])
	{		
		return view($filename, $data)->with($this->viewData);
	}

	public function api(array $data, $encrypt = false)
	{
		return $this->_make_output('api', null, false, $data, $encrypt);
	}

	public function error($message_name = null, $url = false, array $data = [], $export_data = false)
	{
		return $this->_make_output('error', $message_name, $url, $data, $export_data);
	}

	public function success($message_name = null, $url = true, array $data = [], $export_data = true)
	{
		return $this->_make_output('success', $message_name, $url, $data, $export_data);
	}

	public function failure($message_name = null, $url = false, array $data = [],$export_data = false)
	{
		return $this->_make_output('failure', $message_name, $url, $data, $export_data);
	}

	public function warning($message_name = null, $url = false, array $data = [],$export_data = false)
	{
		return $this->_make_output('warning', $message_name, $url, $data, $export_data);
	}

	public function notice($message_name = null, $url = false, array $data = [],$export_data = false)
	{
		return $this->_make_output('notice', $message_name, $url, $data, $export_data);
	}

	protected function error_param($url = false)
	{
		return $this->error('server.error_param',$url);
	}

	protected function success_login($url = true, array $data = [], $export_data = true)
	{
		return $this->success('auth.success_login', $url, $data, $export_data);
	}

	protected function success_logout($url = true, array $data = [], $export_data = true)
	{
		return $this->success('auth.success_logout', $url, $data, $export_data);
	}

	protected function failure_login($url = false, array $data = [], $export_data = false)
	{
		return $this->failure('auth.failure_login', $url, $data, $export_data);
	}

	protected function failure_validate(\Illuminate\Support\MessageBag $messagebag)
	{
		$errors = $messagebag->toArray();
		$messages = [];
		foreach ($errors as $lines) {
			foreach ($lines as $message) {
				$messages[] = trans(Lang::has('validation.failure_post.list') ? 'validation.failure_post.list' : 'core::common.validation.failure_post.list', compact('message'));
			}
		}
		return $this->_make_output('failure', 'validation.failure_post', false, ['errors' => $errors, 'messages' => implode($messages)], true);
	}

	protected function failure_noexists($url = false, array $data = [], $export_data = false)
	{
		return $this->failure('document.failure_noexist', $url, $data, $export_data);
	}

	protected function failure_owner($url = false, array $data = [], $export_data = false)
	{
		return $this->failure('document.failure_owner', $url, $data, $export_data);
	}

	protected function _make_output($type, $message_name = null, $url = false, array $data = [], $export_or_encrypt = false)
	{
		$result = [
			'result' => $type,
			'uid' => !empty($this->user) ? $this->user->getKey() : null,
			'debug' => env('APP_DEBUG'),
		];
		$data = json_decode(json_encode($data), true); //turn Object to Array
		
		switch($type)
		{
			case 'api':
				//加密数据
				if ($export_or_encrypt)
				{
					$encrypt = new OutputEncrypt;
					$key = $encrypt->getEncryptedKey();
					$result += [
						'data' => empty($key) ? null : $encrypt->encode($data), //如果key不对,就不用耗费资源加密了
						'key' => $key,
						'encrypt' => true,
					];
				} else 
					$result += ['data' => $data, 'encrypt' => false];
				break;
			default:
				$msg = $message_name;
				if (!is_array($message_name))
				{
					$msg = Lang::has($message_name) ? trans($message_name) : (Lang::has('core::common.'.$message_name) ? trans('core::common.'.$message_name) : []);
					is_string($msg) && $msg = ['content' => $msg];
					$default = trans('core::common.default.'.$type );
					$msg = _extends($msg, $default); //填充
				}

				$default = trans('core::common.default.'.$type );
				$msg = _extends($msg, $default);

				if (!empty($data))
				{
					foreach ($msg as &$value) 
						$value = __($value, $data); //转化成有意义的文字
				}

				$msg = array_keyfilter($msg, 'title,content');

				$result += [
					'message' => $msg,
					'url' => is_string($url) ? url($url) : $url,
					'data' => $export_or_encrypt ? $data : [],
				];
				break;
		}

		$result += [
			'time' => time(),
			'duration' => microtime(true) - LARAVEL_START,
		];
		return $this->output($result);
	}

	protected function output(array $data)
	{
		$request = app('request');
		$charset = config('app.charset');
		$of = strtolower($request->input('of', $request->expectsJson() ? '' : 'html')); //默认是html
		$callback = $request->query('callback'); //必须是GET请求，以免和POST字段冲突

		$response = null;
		switch ($of) {
			case 'xml':
			case 'txt':
			case 'text':
			case 'html': //text
				$content = $of != 'html' ? Output::$of($data) : $this->view('tips', ['_data' => $data]);
				$response = response($content)->header('Content-Type', Mimes::getInstance()->mime_by_ext($of).'; charset='.$charset);
				break;
			case 'yaml':
			case 'csv':
			case 'xls':
			case 'xlsx':
			case 'pdf': //download
				$filename = Output::$of($data['data']);
				$response = response()->download($filename, date('YmdHis').'.'.$of, ['Content-Type' =>  Mimes::getInstance()->mime_by_ext($of)])->deleteFileAfterSend(true);
				break;
			default: //其余全部为json
				$response = (new JsonResponse($data))->withCallback($callback);
				break;
		}

		if (isset($data['result']) && !in_array($data['result'], ['success', 'api']))
			throw new HttpResponseException($response); // 如果failure 则直接抛出
		return $response;
	}

}