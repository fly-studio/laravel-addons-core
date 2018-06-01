<?php

namespace Addons\Core\Http\Output;

use Illuminate\Support\Manager;
use Addons\Core\Contracts\Http\Output\TipType;
use Illuminate\Contracts\Foundation\Application;

class TipTypeManager extends Manager {

	/**
	 * Create a new manager instance.
	 *
	 * @param  \Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	public function autoDriver($original)
	{
		if ($original instanceof TipType)
			return $original;
		elseif ($original === true)
			return $this->driver('refresh');
		elseif ($original === false)
			return $this->driver('back');
		elseif (is_null($original) || is_numeric($original))
			return $this->driver('toast')->setTimeout($original);
		else
			return $this->driver('redirect')->setUrl($original);
	}

	/**
	 * Create a Toast instance.
	 *
	 * @return \Addons\Core\Http\Output\TipTypes\Toast
	 */
	public function createToastDriver()
	{
		return new TipTypes\ToastType();
	}

	/**
	 * Create a Back instance.
	 *
	 * @return \Addons\Core\Http\Output\TipTypes\BackType
	 */
	public function createBackDriver()
	{
		return new TipTypes\BackType();
	}

	/**
	 * Create a Redirect instance.
	 *
	 * @return \Addons\Core\Http\Output\TipTypes\RedirectType
	 */
	public function createRedirectDriver()
	{
		return new TipTypes\RedirectType();
	}

	/**
	 * Create a Refresh instance.
	 *
	 * @return \Addons\Core\Http\Output\TipTypes\RefreshType
	 */
	public function createRefreshDriver()
	{
		return new TipTypes\RefreshType();
	}

	/**
	 * Create a Refresh instance.
	 *
	 * @return \Addons\Core\Http\Output\TipTypes\RefreshType
	 */
	public function createNullDriver()
	{
		return new TipTypes\NullType();
	}

	/**
	 * Get the default session driver name.
	 *
	 * @return string
	 */
	public function getDefaultDriver()
	{
		return 'null';
	}

}