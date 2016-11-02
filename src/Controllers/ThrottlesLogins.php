<?php

namespace Addons\Core\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Cache;

use Illuminate\Foundation\Auth\ThrottlesLogins as BaseThrottlesLogins;

trait ThrottlesLogins
{
	use BaseThrottlesLogins;

}
