<?php

namespace Dfer\DfPhpCore\Modules;

use Dfer\DfPhpCore\Modules\Db;

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
abstract class Model
{
	/**
	 * 模型名称
	 * @var string
	 */
	protected $name;

	/**
	 * 容器绑定标识
	 * @var array
	 */
	protected $bind = [
		'mysql'  => Mysql::class
	];

	public function __construct(array $data = [])
	{
		global $common;
		// 获取当前模型名称
		if (empty($this->name)) {
			// 当前模型名
			$name       = str_replace('\\', '/', static::class);
			$name = basename($name);
			if (substr($name, -5) == 'Model') {
				$name = substr($name, 0, -5);
			}
			$this->name = $common->unHump($name);
		}
	}

	/**
	 * 获取当前模型的数据库查询对象
	 * @param {Object} $var 变量
	 **/
	public function db($var = null)
	{
		$query = $this->mysql->name($this->name);
		return $query;
	}

	public function __get($name)
	{
		$bind = $this->bind[$name];
		return new $bind;
	}

	/**
	 * 调用不存在的公共方法
	 * @param {Object} $method
	 * @param {Object} $args
	 */
	public function __call($method, $args)
	{
		return call_user_func_array([Db::class, $method], $args);
	}

	/**
	 * 调用不存在的静态方法
	 * @param {Object} $method
	 * @param {Object} $args
	 */
	public static function __callStatic($method, $args)
	{
		// 实例化`Model`类，触发`__construct`方法
		$model = new static();
		return call_user_func_array([$model->db(), $method], $args);
	}
}
