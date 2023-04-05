<?php

if (! function_exists('static_path')) {
    function static_path(string $path = '')
    {
        return normalize_path(config('app.static_path') . (!empty($path) ? DIRECTORY_SEPARATOR.$path : ''));
    }
}

if (! function_exists('utils_path'))
{
    function utils_path(string $path = '')
    {
        return normalize_path(config('app.utils_path') . (!empty($path) ? DIRECTORY_SEPARATOR.$path : ''));
    }
}

if (! function_exists('static_url')) {
    function static_url(string $url = '')
    {
        static $static;

        if (empty($static))
        {
            $static = trim(
                str_replace([public_path(), '\\'], ['', '/'], static_path()),
                '/'
            );
        }

        return url()->asset($static . (!empty($url) ? '/'.$url : ''));
    }
}

if (! function_exists('repo')) {
    function repo(string $className)
    {
        return app('App\\Repositories\\'.studly_case($className).'Repository');
    }
}
