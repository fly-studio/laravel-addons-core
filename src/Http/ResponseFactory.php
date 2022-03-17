<?php

namespace Addons\Core\Http;

use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Addons\Core\Http\Response\BinaryFileResponse;
use Illuminate\Routing\ResponseFactory as FactoryContract;

class ResponseFactory extends FactoryContract {

	/**
	 * Create a new file download response.
	 *
	 * @param  \SplFileInfo|string  $file
	 * @param  string  $name
	 * @param  array  $headers
	 * @param  string|null  $disposition
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
	 */
	public function download($file, $name = null, array $headers = [], $options = [], $disposition = 'attachment')
	{
		!empty($options['cached']) && $headers = array_merge($headers, ['Cache-Control' => 'private, max-age=3600, must-revalidate', 'Pragma' => 'cache']);
		!empty($options['mime_type']) && $headers = array_merge($headers, ['Content-Type' => $options['mime_type']]);

		$response = new BinaryFileResponse($file, 200, $headers, true, $disposition);

		if (isset($options['last_modified']))
			$response->setLastModified($options['last_modified']);

		if (isset($options['etag']))
			$response->setEtag($options['etag']);

		if (!is_null($name))
			$response->setContentDisposition($disposition, $name, str_replace('%', '', Str::ascii($name)));

		return $response;
	}

	public function preview($file, array $headers = [], $options = [])
	{
		return $this->download($file, null, $headers, $options, null);
	}
}
