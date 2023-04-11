<?php

namespace Addons\Core\Controllers;

use Addons\Core\Controllers\OutputTrait;
use Illuminate\Routing\Controller as BaseController;
use Addons\Core\Exceptions\PermissionException;

class Controller extends BaseController {

    use OutputTrait;

    private $_enums = [];

    protected $permissionAuditor = null;

    protected function setPermissionAuditor(callable $callback): static {
        $this->permissionAuditor = $callback;

        return $this;
    }

    protected function addEnum(string $class, string $key = null) {
        if (is_null($key)) {
            $key = substr($class, strrpos($class, '\\') + 1);
        }

        $this->_enums[$key] = $class;
    }

    protected function buildEnums()
    {
        // 读取app/enums/下的enum文件
        if (\file_exists(base_path('app/Enums'))) {
            foreach (glob(base_path('app/Enums/*.php')) as $file) {
                require_once $file;
                $class = '\\App\\Enums\\'. basename($file, '.php');
                $this->addEnum($class);
            }
        }

        return array_map(function($class) {
            return array_values(array_map(function($v) {
                return [
                    'key' => $v->key,
                    'value' => $v->value,
                    'description' => $v->description,
                ];
            }, call_user_func([$class, 'getInstances'])));
        }, $this->_enums);
    }

    public function callAction($method, $parameters)
    {
        $this->viewData['_enums'] =  $this->buildEnums();
        $this->viewData['_method'] = $method;

        if (is_callable($this->permissionAuditor)) {
            throw_if(!(call_user_func_array($this->permissionAuditor, [$this, $method, ...$parameters]) ?? true), PermissionException::class, sprintf("No permission to access [%s@%s].", get_class($this), $method), 403);
        }

        $response = $this->{$method}(...array_values($parameters));

        return $response;
    }

}
