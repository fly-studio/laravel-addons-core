<?php

namespace Addons\Core\Controllers\Concerns;

use Auth, URL;

trait ViewTrait {

	protected $viewData = [];

	public function __set($key, $value)
	{
		$this->viewData[$key] = $value;
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

	protected function subtitle($title, $url = NULL, $target = '_self')
	{
		$title = trans($title);
		$titles = config('settings.subtitles', []);
		config(['settings.subtitles' => array_merge($titles, [compact('title', 'url', 'target')])]);
	}

	protected function view($filename, $data = [])
	{
		if (!$this->disableUser)
			$this->viewData['_user'] = Auth::user();
		$this->viewData['_url'] = URL::current();

		return view($filename, $data)->with($this->viewData);
	}

}
