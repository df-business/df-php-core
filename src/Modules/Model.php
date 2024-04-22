<?php

namespace Dfer\DfPhpCore\Modules;

use Dfer\Tools\{Common};

/**
 * +----------------------------------------------------------------------
 * | 模型类
 * +----------------------------------------------------------------------
 *                                            ...     .............
 *                                          ..   .:!o&*&&&&&ooooo&; .
 *                                        ..  .!*%*o!;.
 *                                      ..  !*%*!.      ...
 *                                     .  ;$$!.   .....
 *                          ........... .*#&   ...
 *                                     :$$: ...
 *                          .;;;;;;;:::#%      ...
 *                        . *@ooooo&&&#@***&&;.   .
 *                        . *@       .@%.::;&%$*!. . .
 *          ................!@;......$@:      :@@$.
 *                          .@!   ..!@&.:::::::*@@*.:..............
 *        . :!!!!!!!!!!ooooo&@$*%%%*#@&*&&&&&&&*@@$&&&oooooooooooo.
 *        . :!!!!!!!!;;!;;:::@#;::.;@*         *@@o
 *                           @$    &@!.....  .*@@&................
 *          ................:@* .  ##.     .o#@%;
 *                        . &@%..:;@$:;!o&*$#*;  ..
 *                        . ;@@#$$$@#**&o!;:   ..
 *                           :;:: !@;        ..
 *                               ;@*........
 *                       ....   !@* ..
 *                 ......    .!%$! ..        | AUTHOR: dfer
 *         ......        .;o*%*!  .          | EMAIL: df_business@qq.com
 *                .:;;o&***o;.   .           | QQ: 3504725309
 *        .;;!o&****&&o;:.    ..
 * +----------------------------------------------------------------------
 *
 */
abstract class Model extends Common
{
    /**
     * 模型名称
     * @var string
     */
    protected $name;

    /**
     * JSON数据表字段
     * @var array
     */
    protected $json = [];

    /**
     * JSON数据取出是否需要转换为数组
     * @var bool
     */
    protected $jsonAssoc = false;

    /**
     * 容器绑定标识
     * @var array
     */
    protected $bind = [
        'mysql'  => Mysql::class
    ];

				/**
				 * 静态实例数组
				 * 对各种类实例化一次之后，可以在任意位置复用，不需要再次实例化
				 */
				protected static $instances = [];


    public function __construct(array $data = [])
    {
        // 获取当前模型名称
        if (empty($this->name)) {
            // 当前模型名
            $name       = str_replace('\\', '/', static::class);
            $name = basename($name);
            if (substr($name, -5) == 'Model') {
                $name = substr($name, 0, -5);
            }
												// 转化为下划线
            $this->name = $this->unHump($name);
        }
    }

    /**
     * 获取当前模型的数据库查询对象
     * @param {Object} $var 变量
     */
    public function db($var = null)
    {
        $setup = [
            'name' => $this->name,
            'json' => $this->json,
            'jsonAssoc' => $this->jsonAssoc
        ];
        $query = $this->mysql->setup($setup);
        return $query;
    }

    public function __get($name)
    {
        $class = $this->bind[$name];
								$instance = new $class;
        return $instance;
    }

    /**
     * 调用不存在的公共方法
     * @param {Object} $method
     * @param {Object} $args
     */
    public function __call($method, $args)
    {
								$class = Mysql::class;
								$instance = static::getInstance($bind);
        return call_user_func_array([$instance, $method], $args);
    }

    /**
     * 调用不存在的静态方法
     * @param {Object} $method
     * @param {Object} $args
     */
    public static function __callStatic($method, $args)
    {
        // 实例化`Model`类，触发`__construct`方法
								$class = static::class;
								$instance = static::getInstance($class);
        return call_user_func_array([$instance->db(), $method], $args);
    }

				/**
				 * 获取静态实例
				 * @param {Object} $class
				 */
				public static function getInstance($class)
				{
				    // 没有创建静态实例就创建
				    if (!isset(static::$instances[$class])) {
				        static::$instances[$class] = new $class;
				    }
				    $instance = static::$instances[$class];
				    return $instance;
				}
}
