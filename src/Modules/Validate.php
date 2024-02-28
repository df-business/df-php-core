<?php

namespace Dfer\DfPhpCore\Modules;

use Dfer\Tools\Statics\Common;
use Dfer\DfPhpCore\Modules\Statics\Lang;

/**
 * +----------------------------------------------------------------------
 * | 验证类
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
abstract class Validate
{
	/**
	 * 当前验证规则
	 * @var array
	 */
	protected $rule = [];

	/**
	 * 验证提示信息
	 * @var array
	 */
	protected $message = [];

	/**
	 * 验证场景定义
	 * @var array
	 */
	protected $scene = [];
	/**
	 * 当前验证场景
	 * @var string
	 */
	protected $currentScene;

	/**
	 * 验证结果
	 * @var array
	 */
	public $status = true;

	/**
	 * 设置验证场景
	 * @access public
	 * @param string $name 场景名
	 * @return $this
	 */
	public function scene(string $name)
	{
		// 设置当前场景
		$this->currentScene = $name;

		return $this;
	}

	/**
	 * 场景需要验证的规则
	 * @var array
	 */
	protected $only = [];

	/**
	 * 获取错误信息
	 * @return array|string
	 */
	public function getError()
	{
		return $this->error;
	}


	/**
	 * 数据自动验证
	 * @access public
	 * @param array $data  数据
	 * @param array $rules 验证规则
	 * @return bool
	 */
	public function checkOrigin(array $data, array $rules = [])
	{
		$this->error = [];

		if ($this->currentScene) {
			$this->getScene($this->currentScene);
		}

		if (empty($rules)) {
			// 读取验证规则
			$rules = $this->rule;
		}

		foreach ($rules as $key => $rule) {
			if (strpos($key, '|')) {
				// 字段|描述 用于指定属性名称
				[$key, $title] = explode('|', $key);
			} else {
				$title = $this->field[$key] ?? $key;
			}
			// 场景检测
			if (!empty($this->only) && !in_array($key, $this->only)) {
				continue;
			}

			// 获取数据 支持二维数组
			$value = $this->getDataValue($data, $key);

			// 字段验证
			$result = $this->checkItem($key, $value, $rule, $data, $title);
			// var_dump($key);
			if (true !== $result) {
				// 没有返回true 则表示验证失败
				if (!empty($this->batch)) {
					// 批量验证
					$this->error[$key] = $result;
				} elseif ($this->failException) {
					throw new ValidateException($result);
				} else {
					$this->error = $result;
					$this->status = false;
					return $this;
				}
			}
		}

		if (!empty($this->error)) {
			if ($this->failException) {
				throw new ValidateException($this->error);
			}
			$this->status = false;
		}

		return $this;
	}

	/**
	 * 获取数据验证的场景
	 * @access protected
	 * @param string $scene 验证场景
	 * @return void
	 */
	protected function getScene(string $scene): void
	{
		$this->only = [];

		if (method_exists($this, 'scene' . $scene)) {
			call_user_func([$this, 'scene' . $scene]);
		} elseif (isset($this->scene[$scene])) {
			// 如果设置了验证适用场景
			$this->only = $this->scene[$scene];
		}
	}

	/**
	 * 验证单个字段规则
	 * @access protected
	 * @param  string    $field  字段名
	 * @param  mixed     $value  字段值
	 * @param  mixed     $rules  验证规则
	 * @param  array     $data  数据
	 * @param  string    $title  字段描述
	 * @param  array     $msg  提示信息
	 * @return mixed
	 */
	protected function checkItem($field, $value, $rules, $data, $title = '', $msg = [])
	{
		// 支持多规则验证 require|in:a,b,c|... 或者 ['require','in'=>'a,b,c',...]
		if (is_string($rules)) {
			$rules = explode('|', $rules);
		}

		$i      = 0;
		$result = true;

		foreach ($rules as $key => $rule) {

			// 判断验证类型
			list($type, $rule, $info) = $this->getValidateType($key, $rule);

			// 验证类型
			if (isset(self::$type[$type])) {
				$result = call_user_func_array(self::$type[$type], [$value, $rule, $data, $field, $title]);
			} elseif ('must' == $info || 0 === strpos($info, 'require') || (!is_null($value) && '' !== $value)) {
				// 验证数据
				$result = call_user_func_array([$this, $type], [$value, $rule, $data, $field, $title]);
			} else {
				$result = true;
			}

			if (false === $result) {
				// 验证失败 返回错误信息
				if (!empty($msg[$i])) {
					$message = $msg[$i];
					if (is_string($message) && strpos($message, '{%') === 0) {
						$message = Lang::get(substr($message, 2, -1));
					}
				} else {
					$message = $this->getRuleMsg($field, $title, $info, $rule);
				}
				return $message;
			} elseif (true !== $result) {
				// 返回自定义错误信息
				if (is_string($result) && false !== strpos($result, ':')) {
					$result = str_replace(':attribute', $title, $result);

					if (strpos($result, ':rule') && is_scalar($rule)) {
						$result = str_replace(':rule', (string) $rule, $result);
					}
				}

				return $result;
			}
			$i++;
		}

		return $result;
	}

	/**
	 * 获取验证规则的错误提示信息
	 * @access protected
	 * @param  string    $attribute  字段英文名
	 * @param  string    $title  字段描述名
	 * @param  string    $type  验证规则名称
	 * @param  mixed     $rule  验证规则数据
	 * @return string
	 */
	protected function getRuleMsg($attribute, $title, $type, $rule)
	{

		if (isset($this->message[$attribute . '.' . $type])) {
			$msg = $this->message[$attribute . '.' . $type];
		} elseif (isset($this->message[$attribute][$type])) {
			$msg = $this->message[$attribute][$type];
		} elseif (isset($this->message[$attribute])) {
			$msg = $this->message[$attribute];
		} elseif (isset(self::$typeMsg[$type])) {
			$msg = self::$typeMsg[$type];
		} elseif (0 === strpos($type, 'require')) {
			$msg = self::$typeMsg['require'];
		} else {
			$msg = $title . Lang::get('not conform to the rules');
		}

		if (is_array($msg)) {
			return $this->errorMsgIsArray($msg, $rule, $title);
		}

		return $this->parseErrorMsg($msg, $rule, $title);
	}

	/**
	 * 获取验证规则的错误提示信息
	 * @access protected
	 * @param string $msg   错误信息
	 * @param mixed  $rule  验证规则数据
	 * @param string $title 字段描述名
	 * @return string|array
	 */
	protected function parseErrorMsg(string $msg, $rule, string $title)
	{
		if (0 === strpos($msg, '{%')) {
			$msg = Lang::get(substr($msg, 2, -1));
		} elseif (Lang::has($msg)) {
			$msg = Lang::get($msg);
		}

		if (is_array($msg)) {
			return $this->errorMsgIsArray($msg, $rule, $title);
		}

		// rule若是数组则转为字符串
		if (is_array($rule)) {
			$rule = implode(',', $rule);
		}

		if (is_scalar($rule) && false !== strpos($msg, ':')) {
			// 变量替换
			if (is_string($rule) && strpos($rule, ',')) {
				$array = array_pad(explode(',', $rule), 3, '');
			} else {
				$array = array_pad([], 3, '');
			}

			$msg = str_replace(
				[':attribute', ':1', ':2', ':3'],
				[$title, $array[0], $array[1], $array[2]],
				$msg
			);

			if (strpos($msg, ':rule')) {
				$msg = str_replace(':rule', (string) $rule, $msg);
			}
		}

		return $msg;
	}

	/**
	 * 错误信息数组处理
	 * @access protected
	 * @param array $msg   错误信息
	 * @param mixed  $rule  验证规则数据
	 * @param string $title 字段描述名
	 * @return array
	 */
	protected function errorMsgIsArray(array $msg, $rule, string $title)
	{
		foreach ($msg as $key => $val) {
			if (is_string($val)) {
				$msg[$key] = $this->parseErrorMsg($val, $rule, $title);
			}
		}
		return $msg;
	}

	/**
	 * 获取当前验证类型及规则
	 * @access public
	 * @param  mixed     $key
	 * @param  mixed     $rule
	 * @return array
	 */
	protected function getValidateType($key, $rule)
	{
		// 判断验证类型
		if (!is_numeric($key)) {
			return [$key, $rule, $key];
		}

		if (strpos($rule, ':')) {
			list($type, $rule) = explode(':', $rule, 2);
			if (isset($this->alias[$type])) {
				// 判断别名
				$type = $this->alias[$type];
			}
			$info = $type;
		} elseif (method_exists($this, $rule)) {
			$type = $rule;
			$info = $rule;
			$rule = '';
		} else {
			$type = 'is';
			$info = $rule;
		}

		return [$type, $rule, $info];
	}


	/**
	 * 获取数据值
	 * @access protected
	 * @param array  $data 数据
	 * @param string $key  数据标识 支持二维
	 * @return mixed
	 */
	protected function getDataValue(array $data, $key)
	{
		if (is_numeric($key)) {
			$value = $key;
		} elseif (is_string($key) && strpos($key, '.')) {
			// 支持多维数组验证
			foreach (explode('.', $key) as $key) {
				if (!isset($data[$key])) {
					$value = null;
					break;
				}
				$value = $data = $data[$key];
			}
		} else {
			$value = $data[$key] ?? null;
		}

		return $value;
	}

	/**
	 * 验证字段值是否为有效格式
	 * @access public
	 * @param mixed  $value 字段值
	 * @param string $rule  验证规则
	 * @param array  $data  数据
	 * @return bool
	 */
	public function is($value, string $rule, array $data = []): bool
	{
		switch (Common::camel($rule)) {
			case 'require':
				// 必须
				$result = !empty($value) || '0' == $value;
				break;
			case 'accepted':
				// 接受
				$result = in_array($value, ['1', 'on', 'yes']);
				break;
			case 'date':
				// 是否是一个有效日期
				$result = false !== strtotime($value);
				break;
			case 'activeUrl':
				// 是否为有效的网址
				$result = checkdnsrr($value);
				break;
			case 'boolean':
			case 'bool':
				// 是否为布尔值
				$result = in_array($value, [true, false, 0, 1, '0', '1'], true);
				break;
			case 'number':
				$result = ctype_digit((string) $value);
				break;
			case 'alphaNum':
				$result = ctype_alnum($value);
				break;
			case 'array':
				// 是否为数组
				$result = is_array($value);
				break;
			case 'file':
				$result = $value instanceof File;
				break;
			case 'image':
				$result = $value instanceof File && in_array($this->getImageType($value->getRealPath()), [1, 2, 3, 6]);
				break;
			case 'token':
				$result = $this->token($value, '__token__', $data);
				break;
			default:
				$result = false;
				break;
		}

		return $result;
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
		return call_user_func_array([$model, "{$method}Origin"], $args);
	}
}
