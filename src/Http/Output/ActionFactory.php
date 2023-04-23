<?php

namespace Addons\Core\Http\Output;

use Addons\Core\Contracts\Http\Output\Action;

class ActionFactory {

    const REDIRECT = 'redirect';
    const BACK = 'back';
    const REFRESH = 'refresh';
    const TOAST = 'toast';

    public function make(string $action, ...$config): Action
    {
        return $this->$action(...$config);
    }

    /**
     * Create a ToastAction instance.
     *
     * @return \Addons\Core\Http\Output\Actions\ToastAction
     */
    public function toast(): Actions\ToastAction
    {
        return new Actions\ToastAction();
    }

    /**
     * Create a BackAction instance.
     *
     * @return \Addons\Core\Http\Output\Actions\BackAction
     */
    public function back(): Actions\BackAction
    {
        return new Actions\BackAction();
    }

    /**
     * Create a RedirectAction instance.
     *
     * @return \Addons\Core\Http\Output\Actions\RedirectAction
     */
    public function redirect(string $url): Actions\RedirectAction
    {
        return new Actions\RedirectAction($url);
    }

    /**
     * Create a RefreshAction instance.
     *
     * @return \Addons\Core\Http\Output\Actions\RefreshAction
     */
    public function refresh(): Actions\RefreshAction
    {
        return new Actions\RefreshAction($timeout);
    }

    /**
     * Create a NullAction instance.
     *
     * @return \Addons\Core\Http\Output\Actions\RefreshAction
     */
    public function null(): Actions\NullAction
    {
        return new Actions\NullAction();
    }

}
