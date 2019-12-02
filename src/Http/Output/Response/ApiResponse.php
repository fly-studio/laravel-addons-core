<?php

namespace Addons\Core\Http\Output\Response;

use Illuminate\Support\Arr;
use Addons\Core\Tools\OutputEncrypt;
use Addons\Core\Contracts\Protobufable;
use Addons\Core\Http\Output\Response\TextResponse;

class ApiResponse extends TextResponse implements Protobufable {

	private $encrypted = false;

	public function getOf()
	{
		if ($this->of == 'auto')
		{
			$request = app('request');
			$of = $request->input('of', null);

			if (!in_array($of, ['txt', 'text', 'json', 'xml', 'yaml']))
				$of = '';

			return $of;
		}

		return $this->of;
	}

	public function getMessage()
	{
		return null;
	}

	public function data($data, $rsaKey = false, $rsaType = 'public')
	{
		if (!empty($rsaKey))
		{
			$rsaKey = is_string($rsaKey) ? $rsaKey : urldecode(request()->header('X-RSA'));

			$encryptor = $rsaType == 'public' ? new OutputEncrypt($rsaKey) : new OutputEncrypt(null, $rsaKey);

			$data = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR);

			$encoded = $rsaType == 'public' ? $encryptor->encodeByPublic($data) : $encryptor->encodeByPrivate($data);

			$this->encrypted = $encoded['aesEncrypted'];

			$this->data = $encoded['value']; //如果无法加密成功，则不用返回数据，避免浪费传输

		} else {
			$this->encrypted = null;

			$this->data = json_decode(json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR), true); //turn Object to Array
		}

		return $this;
	}

	public function getEncrypted()
	{
		return $this->encrypted;
	}

	public function getOutputData()
	{
		$encrypted = $this->getEncrypted();
		$data = Arr::except(parent::getOutputData(), ['action', 'message']);

		if (!empty($encrypted))
			return ['encrypted' => $encrypted, 'data' => $data['data']] + $data;

		return $data;
	}

	public function toProtobuf(): \Google\Protobuf\Internal\Message
	{
		$message = parent::toProtobuf();

		if (!empty($this->getEncrypted()))
			$message->setEncrypted($this->getEncrypted());

		return $message;
	}

}
