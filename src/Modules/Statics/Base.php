<?php

/**
 * +----------------------------------------------------------------------
 * | 静态调用
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
 *                 ......    .!%$! ..     | AUTHOR: dfer
 *         ......        .;o*%*!  .       | EMAIL: df_business@qq.com
 *                .:;;o&***o;.   .        | QQ: 3504725309
 *        .;;!o&****&&o;:.    ..          | WEBSITE: http://www.dfer.site
 * +----------------------------------------------------------------------
 *
 */

namespace Dfer\Tools\Statics;

abstract class Base
{
    /**
     * 静态实例数组
     * 对各种类实例化一次之后，可以在任意位置复用，不需要再次实例化
     */
    protected static $instances = [];

    abstract protected function className();


    /**
     * 调用不存在的公共方法
     * 实例化当前对象的时候触发，同样是静态调用目标类，与"__callStatic"的区别在于可以直接获取className
     * @param {Object} $method
     * @param {Object} $args
     */
    public function __call($method, $args)
    {
        $class = $this->className();
        $instance = static::getInstance($class);
        return call_user_func_array([$instance, $method], $args);
    }

    /**
     * 调用不存在的静态方法
     * 由于静态函数会保持其状态，因此它们可能会使用更多的内存，这里将调用静态方法改为调用实例方法
     * @param {Object} $method
     * @param {Object} $args
     */
    public static function __callStatic($method, $args)
    {
        $class = (new static)->className();
        $instance = static::getInstance($class);
        return call_user_func_array([$instance, $method], $args);
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
