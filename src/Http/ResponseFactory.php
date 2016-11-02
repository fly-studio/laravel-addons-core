<?php
namespace Addons\Core\Http;

use Illuminate\Routing\ResponseFactory as FactoryContract;
use Illuminate\Support\Str;
use Addons\Core\Http\Response;
use Addons\Core\Http\BinaryFileResponse;
class ResponseFactory extends FactoryContract {


	/**
	 * Return a new response from the application.
	 *
	 * @param  string  $content
	 * @param  int  $status
	 * @param  array  $headers
	 * @return \Illuminate\Http\Response
	 */
	public function make($content = '', $status = 200, array $headers = [])
	{
		return new Response($content, $status, $headers);
	}

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
		$etag = isset($options['etag']) ? $options['etag'] : false;
		$last_modified = isset($options['last_modified']) ? $options['last_modified'] : true;
		!empty($options['cache']) && $headers = array_merge($headers, ['Cache-Control' => 'private, max-age=3600, must-revalidate', 'Pragma' => 'cache']);
		!empty($options['mime_type']) && $headers = array_merge($headers, ['Content-Type' => $options['mime_type']]);
		$response = new BinaryFileResponse($file, 200, $headers, true, $disposition, $etag, $last_modified);

		if (!is_null($name)) {
			return $response->setContentDisposition($disposition, $name, str_replace('%', '', Str::ascii($name)));
		}

		return $response;
	}

	public function preview($file, $headers = [], $options = [])
	{
		return $this->download($file, null, $headers, $options, null);
	}
}