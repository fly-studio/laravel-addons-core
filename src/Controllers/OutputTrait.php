<?php
namespace Addons\Core\Controllers;

use Addons\Core\Tools\Output;
use Addons\Core\Tools\OutputEncrypt;
use Illuminate\Http\Exception\HttpResponseException;
use Addons\Core\File\Mimes;
use Illuminate\Support\Facades\Lang;

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

	public function api(array $data, $encrypt = FALSE)
	{
		return $this->_make_output('api', NULL, FALSE, $data, $encrypt);
	}

	public function error($message_name = NULL, $url = FALSE, array $data = [], $export_data = FALSE)
	{
		return $this->_make_output('error', $message_name, $url, $data, $export_data);
	}

	public function success($message_name = NULL, $url = TRUE, array $data = [], $export_data = TRUE)
	{
		return $this->_make_output('success', $message_name, $url, $data, $export_data);
	}

	public function failure($message_name = NULL, $url = FALSE, array $data = [],$export_data = FALSE)
	{
		return $this->_make_output('failure', $message_name, $url, $data, $export_data);
	}

	public function warning($message_name = NULL, $url = FALSE, array $data = [],$export_data = FALSE)
	{
		return $this->_make_output('warning', $message_name, $url, $data, $export_data);
	}

	public function notice($message_name = NULL, $url = FALSE, array $data = [],$export_data = FALSE)
	{
		return $this->_make_output('notice', $message_name, $url, $data, $export_data);
	}

	protected function error_param($url = FALSE)
	{
		return $this->error('server.error_param',$url);
	}

	protected function success_login($url = TRUE, array $data = [], $export_data = TRUE)
	{
		return $this->success('auth.success_login', $url, $data, $export_data);
	}

	protected function success_logout($url = TRUE, array $data = [], $export_data = TRUE)
	{
		return $this->success('auth.success_logout', $url, $data, $export_data);
	}

	protected function failure_login($url = FALSE, array $data = [], $export_data = FALSE)
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
		return $this->_make_output('failure', 'validation.failure_post', FALSE, ['errors' => $errors, 'messages' => implode($messages)], TRUE);
	}

	protected function failure_noexists($url = FALSE, array $data = [], $export_data = FALSE)
	{
		return $this->failure('document.failure_noexist', $url, $data, $export_data);
	}

	protected function failure_owner($url = FALSE, array $data = [], $export_data = FALSE)
	{
		return $this->failure('document.failure_owner', $url, $data, $export_data);
	}

	protected function _make_output($type, $message_name = NULL, $url = FALSE, array $data = [], $export_or_encrypt = FALSE)
	{
		$result = [
			'result' => $type,
			'uid' => !empty($this->user) ? $this->user->getKey() : NULL,
			'debug' => env('APP_DEBUG'),
		];

		switch($type)
		{
			case 'api':
				//加密数据
				if ($export_or_encrypt)
				{
					$encrypt = new OutputEncrypt;
					$key = $encrypt->getEncryptedKey();
					$result += [
						'data' => empty($key) ? NULL : $encrypt->encode($data), //如果key不对,就不用耗费资源加密了
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
			'duration' => microtime(TRUE) - LARAVEL_START,
		];
		return $this->output($result);
	}

	protected function output(array $data, $filename = NULL)
	{
		$request = app('request');
		$charset = config('app.charset');
		$of = strtolower($request->input('of'));
		$jsonp = isset($_GET['jsonp']) ? $_GET['jsonp'] : null; 
		empty($jsonp) && $jsonp = isset($_GET['callback']) ? $_GET['callback'] : null;

		if (!in_array($of, ['js', 'json', 'jsonp', 'xml', 'txt', 'text', 'csv', 'xls', 'xlsx', 'yaml', 'html', 'pdf' ]))
		{
			if ($request->expectsJson()) //自动切换ajax状态下of为json
				$of = empty($jsonp) ? 'json' : 'jsonp';
			else
				$of = 'html';
		}
		$response = null;
		if (in_array($of, ['csv', 'xls', 'xlsx', 'pdf']))
		{
			$filename = Output::$of($data['data']);
			$response = response()->download($filename, date('YmdHis').'.'.$of, ['Content-Type' =>  Mimes::getInstance()->mime_by_ext($of)])->deleteFileAfterSend(TRUE);
		} else {
			$content = $of != 'html' ? Output::$of($data, $jsonp) : $this->view('tips', ['_data' => $data]);
			$response = response($content)->header('Content-Type', Mimes::getInstance()->mime_by_ext($of).'; charset='.$charset);
		}
		if (isset($data['result']) && !in_array($data['result'], ['success', 'api'])) throw new HttpResponseException($response); // 如果failure 则直接抛出
		return $response;
	}

}