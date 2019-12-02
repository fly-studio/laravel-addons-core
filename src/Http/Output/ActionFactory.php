<?php

namespace Addons\Core\Http\Output;

class ActionFactory {

	const REDIRECT = 'redirect';
	const BACK = 'back';
	const REFRESH = 'refresh';
	const TOAST = 'toast';

	public function make(string $action, ...$config)
	{
		return $this->$action(...$config);
	}

	/**
	 * Create a ToastAction instance.
	 *
	 * @return \Addons\Core\Http\Output\Actions\ToastAction
	 */
	public function toast(int $timeout = 1500)
	{
		return new Actions\ToastAction($timeout);
	}

	/**
	 * Create a BackAction instance.
	 *
	 * @return \Addons\Core\Http\Output\Actions\BackAction
	 */
	public function back()
	{
		return new Actions\BackAction();
	}

	/**
	 * Create a RedirectAction instance.
	 *
	 * @return \Addons\Core\Http\Output\Actions\RedirectAction
	 */
	public function redirect(string $url, int $timeout = 1500)
	{
		return new Actions\RedirectAction($timeout, $url);
	}

	/**
	 * Create a RefreshAction instance.
	 *
	 * @return \Addons\Core\Http\Output\Actions\RefreshAction
	 */
	public function refresh(int $timeout = 1500)
	{
		return new Actions\RefreshAction($timeout);
	}

	/**
	 * Create a NullAction instance.
	 *
	 * @return \Addons\Core\Http\Output\Actions\RefreshAction
	 */
	public function null()
	{
		return new Actions\NullAction();
	}

}
