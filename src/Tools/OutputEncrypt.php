<?php

namespace Addons\Core\Tools;

use phpseclib\Crypt\RSA;
use Addons\Core\Tools\Encrypter;

class OutputEncrypt {

    private $publicKey;
    private $privateKey;
    private $aesKey;

    public function __construct(string $publicKey = null, string $privateKey = null)
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    public function encodeByPublic(string $value, string $aesKey = null)
    {
        $encoded = $this->encryptAesToRaw($value, $aesKey);

        $aesEncrypted = $this->encryptPublicRsa($encoded['aesBase64']);

        return [
            'aesEncrypted' => !empty($aesEncrypted) ? base64_encode($aesEncrypted) : null,
            'value' => !empty($aesEncrypted) ? base64_encode($encoded['value']) : null
        ];
    }

    public function encodeByPrivate(string $value, string $aesKey = null)
    {
        $encoded = $this->encryptAesToRaw($value, $aesKey);

        $aesEncrypted = $this->encryptPrivateRsa($encoded['aesBase64']);

        return [
            'aesEncrypted' => !empty($aesEncrypted) ? base64_encode($aesEncrypted) : null,
            'value' => !empty($aesEncrypted) ? base64_encode($encoded['value']) : null
        ];
    }

    public function decodeByPublic(string $value, string $aesEncrypted)
    {
        $decoded = $this->decryptPublicRsa(base64_decode($aesEncrypted));

        if (empty($decoded))
            return null;

        return $this->decryptAesFromRaw($value, $decoded);
    }

    public function decodeByPrivate(string $value, string $aesEncrypted)
    {
        $decoded = $this->decryptPrivateRsa(base64_decode($aesEncrypted));

        if (empty($decoded))
            return null;

        return $this->decryptAesFromRaw($value, $decoded);
    }

    private function makePrivateRsa()
    {
        $rsa = new RSA;
        $rsa->setPrivateKey();
        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        $rsa->loadKey($this->privateKey);

        return $rsa;
    }

    private function makePublicRsa()
    {
        $rsa = new RSA;
        $rsa->setPublicKey();
        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        $rsa->loadKey($this->publicKey);

        return $rsa;
    }

    public function encryptPrivateRsa(string $value)
    {
        //return $this->makePrivateRsa()->encrypt($value);
        $key = openssl_pkey_get_private($this->privateKey);
         $bitLen = openssl_pkey_get_details ($key)['bits'] / 8 - 11;
        $result = ''; $offset = 0; $len = strlen($value);
        while($offset < $len)
        {
            $read = min($len - $offset, $bitLen);
             openssl_private_encrypt(substr($value, $offset, $bitLen), $res, $key, OPENSSL_PKCS1_PADDING);
             $result .= $res;
             $offset += $read;
        }
         openssl_free_key($key);
        return $result;
    }

    public function decryptPrivateRsa(string $value)
    {
        //return $this->makePrivateRsa()->decrypt($value);
        $key = openssl_pkey_get_private($this->privateKey);
        $bitLen = openssl_pkey_get_details ($key)['bits'] / 8;
        $result = ''; $offset = 0; $len = strlen($value);
        while($offset < $len)
        {
            $read = min($len - $offset, $bitLen);
             openssl_private_decrypt(substr($value, $offset, $bitLen), $res, $key, OPENSSL_PKCS1_PADDING);
             $result .= $res;
             $offset += $read;
        }
         openssl_free_key($key);
        return $result;
    }

    public function encryptPublicRsa(string $value)
    {
        //return $this->makePublicRsa()->encrypt($value);
        $key = openssl_pkey_get_public($this->publicKey);
         $bitLen = openssl_pkey_get_details ($key)['bits'] / 8 - 11;
        $result = ''; $offset = 0; $len = strlen($value);
        while($offset < $len)
        {
            $read = min($len - $offset, $bitLen);
             openssl_public_encrypt(substr($value, $offset, $bitLen), $res, $key, OPENSSL_PKCS1_PADDING);
             $result .= $res;
             $offset += $read;
        }
         openssl_free_key($key);
        return $result;
    }
    public function decryptPublicRsa(string $value)
    {
        //return $this->makePublicRsa()->decrypt($value);
        $key = openssl_pkey_get_public($this->publicKey);
         $bitLen = openssl_pkey_get_details ($key)['bits'] / 8;
        $result = ''; $offset = 0; $len = strlen($value);
        while($offset < $len)
        {
            $read = min($len - $offset, $bitLen);
             openssl_public_decrypt(substr($value, $offset, $read), $res, $key, OPENSSL_PKCS1_PADDING);
             $result .= $res;
             $offset += $read;
        }
         openssl_free_key($key);
        return $result;
    }

    public function encryptAes(string $value, string $aesKey = null)
    {
        $e = new Encrypter($aesKey ?? $this->aesKey, config('app.cipher'));

        return $e->encrypt($value, false);
    }

    public function decryptAes(string $value, string $iv, string $mac, string $aesKey = null)
    {
        $e = new Encrypter($aesKey ?? $this->aesKey, config('app.cipher'));

        return $e->decrypt(compact('value', 'iv', 'mac'), false);
    }

    private function encryptAesToRaw(string $value, string $aesKey = null)
    {
        $aes = [
            'iv' => '',
            'mac' => '',
            'key' => $aesKey ?? $this->generateAesKey()
        ];

        $encoded = $this->encryptAes($value, $aes['key']);

        $aes['iv'] = base64_encode($encoded['iv']);
        $aes['mac'] = $encoded['mac'];
        $aes['key'] = base64_encode($aes['key']);

        $aesBase64 = json_encode($aes, JSON_PARTIAL_OUTPUT_ON_ERROR);

        return ['aesBase64' => $aesBase64, 'value' => $encoded['value']];
    }

    private function decryptAesFromRaw(string $value, string $decoded)
    {
        $aes = json_decode($decoded, true);
        $aes['key'] = base64_decode($aes['key']);

        return $this->decryptAes(base64_decode($value), $aes['iv'], $aes['mac'], $aes['key']);
    }

    public function generateAesKey()
    {
        return $this->aesKey = Encrypter::generateKey(config('app.cipher'));
    }

    public function generateRsaKeys()
    {
        $rsa = new RSA;
        $rsaKeys = $rsa->createKey(2048);

        $this->publicKey = $rsaKeys['publickey'];
        $this->privateKey = $rsaKeys['privatekey'];

        return $rsaKeys;
    }

}
