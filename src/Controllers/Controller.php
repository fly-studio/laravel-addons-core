<?php

namespace Addons\Core\Controllers;

use Addons\Core\Events\ControllerEvent;
use Addons\Core\Controllers\OutputTrait;
use Illuminate\Routing\Controller as BaseController;
use Addons\Censor\Validation\ValidatesRequests;
use Addons\Entrust\Controllers\PermissionTrait;


class Controller extends BaseController {

    use PermissionTrait, OutputTrait, ValidatesRequests;

    protected $disableUser = false;

    private $enums = [];

    protected function addEnum(string $class, string $key = null) {
        if (is_null($key)) {
            $key = substr($class, strrpos($class, '\\') + 1);
        }

        $this->enums[$key] = $class;
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
        }, $this->enums);
    }

    public function callAction($method, $parameters)
    {
        // check current user's permissions
        if (!$this->disableUser)
            $this->checkPermission($method);

        $this->viewData['_enums'] =  $this->buildEnums();
        $this->viewData['_permissionTable'] = $this->permissionTable;
        $this->viewData['_method'] = $method;

        $response = $this->{$method}(...array_values($parameters));

        return $response;
    }

}
