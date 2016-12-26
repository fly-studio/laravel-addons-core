<?php

namespace Addons\Core\Contracts\Listeners;

use Illuminate\Support\Str;

class ControllerListener
{
	protected function loadControllerListeners($className, $methodName, $parameters, $guessClassPostfix = 'Listener')
	{
		$listenerClasses = [];
		foreach($this->controllerListeners as $key => $listeners)
			if (Str::is($key, $className))
				array_merge($listenerClasses, $listeners);

		//猜测监听器类名、方法名
		$guessClass = str_replace('\Http\Controllers\\', '\Listeners\\', $className).$guessClassPostfix;
		$guessMethod = $methodName;

		empty($listenerClasses) && class_exists($guessClass) && $listenerClasses[] = $guessClass;

		foreach ($listenerClasses as $listener)
		{
			if (!Str::contains($listener, '@') && method_exists($listener, $guessMethod)) $listener .= '@'.$guessMethod; 
			call_user_func_array(app('events')->makeListener($listener), $parameters);
		}
		return true;
	}
}

