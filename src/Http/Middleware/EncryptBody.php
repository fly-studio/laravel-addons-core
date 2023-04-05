<?php

namespace Addons\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Addons\Core\Tools\OutputEncrypt;
use Addons\Core\Http\Output\Response\TextResponse;

class EncryptBody
{
    protected $encrypter;
    protected $rsa_type;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, string $rsaKey = null)
    {
        if (!empty($rsaKey))
        {
            $this->encrypter = new OutputEncrypt(null, $rsaKey);
            $this->rsa_type = 'private';
        } else {
            $rsaKey = urldecode($request->header('X-RSA'));
            $this->encrypter = new OutputEncrypt($rsaKey);
            $this->rsa_type = 'public';
        }

        return !empty($rsaKey) ? $this->encrypt($next($this->decrypt($request))) : $next($request);
    }

    public function encrypt($response)
    {
        if ($response instanceOf TextResponse) {

            $data = $response->getData();
            if (empty($response->getEncrypted()) && (is_numeric($data) || !empty($data)))
            {
                $data = json_encode($response->getData(), JSON_PARTIAL_OUTPUT_ON_ERROR);

                $encoded = $this->rsa_type == 'public' ? $this->encrypter->encodeByPublic($data) : $this->encrypter->encodeByPrivate($data);

                $response->encrypted($encoded['aesEncrypted']);

                $response->data($encoded['value'], true); //如果无法加密成功，则不用返回数据，避免浪费传输
            }
        }

        return $response;
    }

    protected function decrypt(Request $request)
    {
        if (in_array(strtoupper($request->method()), ['GET', 'POST', 'PUT', 'DELETE']))
        {
            $encrypted = $request->input('encrypted');
            $data = $request->input('data');

            if (!empty($encrypted) && !empty($data))
            {
                $json = $this->rsa_type == 'public' ? $this->encrypter->decodeByPublic($data, $encrypted) : $this->encrypter->decodeByPrivate($data, $encrypted);

                // object json
                if (!empty($json) && $json[0] === '{')
                {
                    $request->offsetUnset('encrypted');
                    $request->offsetUnset('data');
                    $request->merge(json_decode($json, true));
                }
            }
        }

        return $request;
    }
}
