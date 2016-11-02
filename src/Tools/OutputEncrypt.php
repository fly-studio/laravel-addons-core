<?php
namespace Addons\Core\Tools;

use phpseclib\Crypt\RSA;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Encryption\Encrypter;

class OutputEncrypt {

	public static $key;

	public function getEncryptedKey()
	{
		$request = request();
		$public = urldecode($request->header('X-RSA'));
		$rsa = new RSA;
		$rsa->setPublicKey();
		$rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
		$rsa->loadKey($public);
		$key = $rsa->encrypt(base64_encode($this->getKey()));
		return $key ? base64_encode($key) : false;
	}

	public function encode($data)
	{
		$e = new Encrypter($this->getKey(), config('app.cipher'));
		return $e->encrypt($data);
	}

	public function getKey()
	{
		if (!empty(static::$key)) return static::$key;

		static::$key = session('output-key');
		empty(static::$key) && static::$key = random_bytes(config('app.cipher') == 'AES-128-CBC' ? 16 : 32);
		
		session(['output-key' => static::$key]);
		session()->save();
		return static::$key;
	}



}