<?php
namespace Addons\Core\Http;

use Illuminate\Routing\UrlGenerator as BaseUrlGenerator;
class UrlGenerator extends BaseUrlGenerator {

	public function to($path, $extra = [], $secure = null)
	{
		if ($this->isValidUrl($path)) {
			return $path;
		}
		$scheme = $this->getScheme($secure);

		$extra = $this->formatParameters($extra);

		$tail = implode('/', array_map(
			'rawurlencode', (array) $extra)
		);

		$root = $this->getRootUrl($scheme, config('app.url'));

		if (($queryPosition = strpos($path, '?')) !== false) {
			$query = substr($path, $queryPosition);
			$path = substr($path, 0, $queryPosition);
		} else {
			$query = '';
		}
		return $this->trimUrl(rtrim($root, '/'), $path, $tail).$query;
	}
}