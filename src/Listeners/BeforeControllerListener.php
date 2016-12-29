<?php

namespace Addons\Core\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Addons\Core\Contracts\Events\ControllerEvent;
use Addons\Core\Contracts\Listeners\ControllerListener as ControllerListenerContract;

abstract class BeforeControllerListener extends ControllerListenerContract implements ShouldQueue
{
	use InteractsWithQueue;

	protected $controllerListeners = [
		// eg: Admin\MemberController edit
		// if not matched, auto call class 'App\Listener\Admin\MemberControllerBeforeListener@edit' 
		// 'App\Http\Controllers\Home*' => [
		// 		'App\Listener\HomeControllerBeforeListener', //auto call current controller's method
		// 		'App\Listener\HomeControllerBeforeListener@defined_method',
		// 	],
	];

	public function handle(ControllerEvent $event) 
	{
		$className = $event->getClassName();
		$methodName = $event->getMethod();

		return $this->loadControllerListeners($className, $methodName, func_get_args(), 'BeforeListener');
	}
}