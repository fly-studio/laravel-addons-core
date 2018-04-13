<?php

namespace Addons\Core\Tools;

use phpseclib\Crypt\RSA;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Encryption\Encrypter;

class OutputEncrypt {

	public static $key;

	public function getClientEncryptedKey()
	{
		$public = urldecode(request()->header('X-RSA'));
		$rsa = new RSA;
		$rsa->setPublicKey();
		$rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
		$rsa->loadKey($public);
		$key = $rsa->encrypt(base64_encode($this->getAesKey()));
		return $key ? base64_encode($key) : false;
	}

	public function getServerEncryptedKey()
	{
		$private = $this->getRsaPrivateKey();
		$rsa = new RSA;
		$rsa->setPrivateKey();
		$rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
		$rsa->loadKey($private);
		$key = $rsa->encrypt(base64_encode($this->getAesKey()));
		return $key ? base64_encode($key) : false;
	}

	public function encode($data)
	{
		$e = new Encrypter($this->getAesKey(), config('app.cipher'));
		return $e->encrypt($data);
	}

	public function decode($data)
	{
		$e = new Encrypter($this->getAesKey(), config('app.cipher'));
		return $e->decrypt($data);
	}

	public function getAesKey()
	{
		$key = session('client.encrpted.aes');
		if (empty($key)) {
			$key = random_bytes(config('app.cipher') == 'AES-128-CBC' ? 16 : 32);
			session(['client.encrpted.aes' => $key]);
			session()->save();
		}
		return $key;
	}

	public function getRsaKeys($key = null)
	{
		$keys = session('client.encrpted.rsa');
		if (empty($keys)) {
			$rsa = new RSA;
			$_keys = $rsa->createKey(2048);
			session(['client.encrpted.rsa' => $keys]);
			session()->save();
		}
		return is_null($key) ? $keys : $keys[$key];
	}

	public function getRsaPublicKey()
	{
		return $this->getRsaKeys('publickey');
	}

	public function getRsaPrivateKey()
	{
		return $this->getRsaKeys('privatekey');
	}

}