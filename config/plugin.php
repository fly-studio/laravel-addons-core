<?php
//本文件只是示例文件，请使用config('plugins.{PLUGINNAME}')调取对应插件的配置，
//{PLUGINNAME}对应下文的'name'，这里假设为tools
//插件的namespace假设为Plugins\Tools

return [
	'enable' => FALSE, //开关，已停止的插件，建议设置FALSE，以免浪费资源
	//插件名(英文、数字、-、_)，全局唯一，符合PHP变量名规范，为空代表使用当前文件夹名，
	'name' => NULL, 
	'display_name' => '', //本插件的名称，比如：工具箱
	'description' => '', //本插件功能简介
	//本插件的namespace，为空代表使用name，-_在转换为namespace会变为驼峰
	//[name]      转    [namespace]
	//tools       ->    Plugins\Tools
	//wechat-abc  ->    Plugins\WechatAbc
	//hi_world    ->    Plugins\HiWorld
	'namespace' => NULL, 
	'path' => NULL, //本插件的路径，这个值不用赋值，会被程序自动配置
	'register' => [ //注册namespace
		//是否注册/tools/resources/views到视图
		//- Controller中这样调用：view('tools:system.xxx'); 对应/tools/resources/views/system/xxx.tpl
		//- smarty模板中这样调用：<{include file="[tools]system/nav.inc.tpl"}>
		'view' => false, 
		//是否注册/tools/database/migrations到迁移
		'migrate' => false, 
		//是否注册/tools/resources/lang到语言包
		//- Controller中这样调用：lang('tools:valition.alpha_dash');
		//- smarty模板中这样调用：<{'tools:valition.alpha_dash'|lang}>
		'translator' => false,
		//是否自动设置路由
		//- 自动加载/tools/routes.php
		//- 当为true时，调用下文router的namespace,prefix,middleware配置
		'router' => false,
		//是否将/tools/config/validation.php合并到主配置config/validation.php
		//- 示例可以查看tools/config/validation.php
		//- 注意：相同键名会被覆盖
		'validation' => false,
	],
	'routers' => [ //Route::group(['namespace' => '?', 'prefix' => '?', 'middleware' => '?']);
		'web' => [
			'namespace' => NULL, //本插件下Controller的路由的namespace，空代表使用Plugins\tools\App\Http\Controllers
			'prefix' => '/', //路由的prefix，空代表 / (根目录)
			'middleware' => [], //路由的中间件，不启用中间件时，必须为空数组，不能设为NULL
		],
		'api' => [
			'namespace' => NULL, //本插件下Controller的路由的namespace，空代表使用Plugins\tools\App\Http\Controllers
			'prefix' => 'api', //路由的prefix，空代表 / (根目录)
			'middleware' => [], //路由的中间件，不启用中间件时，必须为空数组，不能设为NULL
		],
		//....
	],
	//全局中间件组，会被自动调用
	//参考 /app/Http/Kernel.php middlewareGroups
	'middlewareGroups' => [
		// 'web' => [],
		// 'api' => [],
	],
	//路由中间键 附加到路由中
	//参考 /app/Http/Kernel.php routeMiddleware
	'routeMiddleware' => [
		// 'cry' => \Plugins\Tools\App\Http\Middleware\Cry::class, 
	],
	//自定义artisan命令
	//参考 /app/Console/Kernel.php commands
	'commonds' => [
        //\Plugins\Tools\App\Console\Commands\Inspire::class,
	],
	//插件中的模板注入到主模板（确保相同路径）暂只支持smarty
	//- 注意：注入不是智能的，只有当主模板中有<{pluginclude file='admin/sidebar.inc.tpl'}>时，程序会尝试按照顺序插入所有插件中的模板
	//- 插入指定插件的模板：<{pluginclude file='admin/sidebar.inc.tpl' plugins="tools,wechat,xxx"}> 或者使用原生语句<{include file='[tools]admin/sidebar.inc.tpl'}>
	//- 为避免模板被重复(死递归)嵌套 pluginclude子模板中使用pluginclude会报错
	'injectViews' => [
		// 比如 管理员后台的菜单，会尝试<{include file="[tools]admin/sidebar.inc.tpl"}>
		// 没有这行，不会插入
		// 'admin/sidebar.inc.tpl',
	],
	//需要读取的配置文件，请勿加入plugin,validation
	'config' => [
		//比如config/attachment.php
		//'attachment',
	],

];

