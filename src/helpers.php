<?php

if (! function_exists('static_path')) {
    function static_path(string $path = '')
    {
        return normalize_path(config('app.static_path', public_path('static')) . (!empty($path) ? DIRECTORY_SEPARATOR.$path : ''));
    }
}

if (! function_exists('utils_path'))
{
    function utils_path(string $path = '')
    {
        return normalize_path(config('app.utils_path', public_path('utils')) . (!empty($path) ? DIRECTORY_SEPARATOR.$path : ''));
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

if (! function_exists('relative_path')) {
/**
 * 根据base_path，取出target_path相对路径
 * @example
 * input: relative_path('/var/www/home/1.php', '/var/www/')
 * output: ./home/1.php
 *
 * @param  string $target_path 绝对路径
 * @param  string $base_path   根目录路径
 * @return string              输出相对路径
 */
function relative_path($target_path, $base_path = __FILE__ )
{
    // some compatibility fixes for Windows paths
    $base_path = is_dir($base_path) ? rtrim($base_path, '\/') . '/' : $base_path;
    $target_path = is_dir($target_path) ? rtrim($target_path, '\/') . '/'   : $target_path;
    $base_path = str_replace('\\', '/', $base_path);
    $target_path = str_replace('\\', '/', $target_path);

    $base_path = explode('/', $base_path);
    $target_path = explode('/', $target_path);
    $relPath  = $target_path;

    foreach($base_path as $depth => $dir) {
        // find first non-matching dir
        if($dir === $target_path[$depth]) {
            // ignore this directory
            array_shift($relPath);
        } else {
            // get number of remaining dirs to $base_path
            $remaining = count($base_path) - $depth;
            if($remaining > 1) {
                // add traversals up to first matching dir
                $padLength = (count($relPath) + $remaining - 1) * -1;
                $relPath = array_pad($relPath, $padLength, '..');
                break;
            } else {
                $relPath[0] = './' . $relPath[0];
            }
        }
    }
    return implode('/', $relPath);
}
}
if (! function_exists('normalize_path')) {
/**
 * 去掉路径中多余的..或/
 * @example
 * Will convert /path/to/test/.././..//..///..///../one/two/../three/filename
 * to ../../one/three/filename
 *
 * @param  string $path 输入路径
 * @return string       输出格式化之后的路径
 */
function normalize_path($path, $separator = DIRECTORY_SEPARATOR)
{
    $parts = [];// Array to build a new path from the good parts
    $path = str_replace('\\', '/', $path);// Replace backslashes with forwardslashes
    $path = preg_replace('/\/+/', '/', $path);// Combine multiple slashes into a single slash
    $segments = explode('/', $path);// Collect path segments
    $test = '';// Initialize testing variable
    foreach($segments as $segment)
    {
        if($segment != '.')
        {
            $test = array_pop($parts);
            if(is_null($test))
                $parts[] = $segment;
            else if($segment == '..')
            {
                if($test == '..')
                    $parts[] = $test;

                if($test == '..' || $test == '')
                    $parts[] = $segment;
            }
            else
            {
                $parts[] = $test;
                $parts[] = $segment;
            }
        }
    }
    return implode($separator, $parts);
}
}

if (! function_exists('fileinfo')) {
/**
 * 返回文件的一些基本属性
 *
 * @param  string $path 文件路径
 * @return array        返回属性数组
 */
function fileinfo($path)
{
    $stat = /*array(
        'uid' => fileowner($path),
        'gid' => filegroup($path),
        'size' => filesize($path),
        'mtime' => filemtime($path),
        'atime' => fileatime($path),
        'ctime' => filectime($path),
    );*/stat($path);
    return array(
        'type' => is_dir($path) ? 'dir' : (is_file($path) ? 'file' : 'other') /*filetype($path) 比较耗费资源*/,
        'path' => $path,
        'uid' => $stat['uid'],
        'gid' => $stat['gid'],
        'size' => $stat['size'],
        'mtime' => $stat['mtime'],
        'atime' => $stat['atime'],
        'atime' => $stat['atime'],
        'ctime' => $stat['ctime'],
        'nlink' => $stat['nlink'],
        //'readable' => is_readable($path), /*比较耗费资源*/
        //'writable' => is_writable($path), /*比较耗费资源*/
    );
}
}