<?php

namespace Dfer\DfPhpCore\Modules\Statics;


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
 *                 ......    .!%$! ..        | AUTHOR: dfer
 *         ......        .;o*%*!  .          | EMAIL: df_business@qq.com
 *                .:;;o&***o;.   .           | QQ: 3504725309
 *        .;;!o&****&&o;:.    ..
 * +----------------------------------------------------------------------
 *
 */
abstract class Base
{

			abstract protected function originClass($var = null);


			/**
				* 调用不存在的公共方法
				* @param {Object} $method
				* @param {Object} $args
				*/
			public  function __call($method, $args)
			{
							$class=$this->originClass();
			    return call_user_func_array([$class,$method], $args);
			}

			/**
				* 调用不存在的静态方法
				* 由于静态函数会保持其状态，因此它们可能会使用更多的内存，这里将调用静态方法改为调用实例方法
				* @param {Object} $method
				* @param {Object} $args
				*/
			public static function __callStatic($method, $args)
			{
						$class=(new static)->originClass();
						return call_user_func_array([$class,$method], $args);
			}

}
