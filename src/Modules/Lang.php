<?php

namespace Dfer\DfPhpCore\Modules;

use Dfer\Tools\Common;


/**
 * +----------------------------------------------------------------------
 * | 语言类
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
class Lang
{

	/**
	 * 配置参数
	 * @var array
	 */
	protected $config = [
		// 默认语言
		'default_lang'    => 'zh-cn',
		// 多语言cookie变量
		'cookie_var'    => 'lang',
		// Accept-Language转义为对应语言包名称
		'accept_language' => [
			'zh-hans-cn' => 'zh-cn',
		],
		// 允许的语言列表
		'allow_lang_list' => []
	];

	/**
	 * 当前语言
	 * @var string
	 */
	private $range = 'zh-cn';

	public function __construct()
	{
		if (LANG_DETECT) {
			// 自动侦测当前语言
			$langset = $this->detect($request);

			if ($this->defaultLangSet() != $langset) {
				$this->switchLangSet($langset);
			}

			$this->saveToCookie($langset);
		} else {
			$this->switchLangSet($this->defaultLangSet());
		}
	}

	/**
	 * 自动侦测设置获取语言选择
	 * @access protected
	 * @param Request $request
	 * @return string
	 */
	protected function detect(): string
	{
		// 自动侦测设置获取语言选择
		$langSet = '';


		if ($_COOKIE[$this->config['cookie_var']]) {
			// Cookie中设置了语言变量
			$langSet = $_COOKIE[$this->config['cookie_var']];
		} elseif ($_SERVER['HTTP_ACCEPT_LANGUAGE']) {
			// 自动侦测浏览器语言
			$langSet = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}

		if (preg_match('/^([a-z\d\-]+)/i', $langSet, $matches)) {
			$langSet = strtolower($matches[1]);
			if (isset($this->config['accept_language'][$langSet])) {
				$langSet = $this->config['accept_language'][$langSet];
			}
		} else {
			$langSet = $this->getLangSet();
		}

		if (empty($this->config['allow_lang_list']) || in_array($langSet, $this->config['allow_lang_list'])) {
			// 合法的语言
			$this->setLangSet($langSet);
		} else {
			$langSet = $this->getLangSet();
		}

		return $langSet;
	}
	/**
	 * 保存当前语言到Cookie
	 * @access protected
	 * @param Cookie $cookie Cookie对象
	 * @param string $langSet 语言
	 * @return void
	 */
	protected function saveToCookie(string $langSet)
	{
		cookie_set($this->config['cookie_var'], $langSet, SESSION_EXPIRES);
	}

	/**
	 * 获取默认语言
	 * @access public
	 * @return string
	 */
	public function defaultLangSet()
	{
		return $this->config['default_lang'];
	}
	/**
	 * 获取当前语言
	 * @access public
	 * @return string
	 */
	public function getLangSet(): string
	{
		return $this->range;
	}

	/**
	 * 加载语言定义(不区分大小写)
	 * @access public
	 * @param string|array $file  语言文件
	 * @param string       $range 语言作用域
	 * @return array
	 */
	public function load($file, $range = ''): array
	{
		$range = $range ?: $this->range;
		if (!isset($this->lang[$range])) {
			$this->lang[$range] = [];
		}

		$lang = [];

		foreach ((array) $file as $name) {
			if (is_file($name)) {
				$result = $this->parse($name);
				$lang   = array_change_key_case($result) + $lang;
			}
		}

		if (!empty($lang)) {
			$this->lang[$range] = $lang + $this->lang[$range];
		}
		return $this->lang[$range];
	}

	/**
	 * 解析语言文件
	 * @access protected
	 * @param string $file 语言文件名
	 * @return array
	 */
	protected function parse(string $file): array
	{
		$type = pathinfo($file, PATHINFO_EXTENSION);

		switch ($type) {
			case 'php':
				$result = include $file;
				break;
			case 'yml':
			case 'yaml':
				if (function_exists('yaml_parse_file')) {
					$result = yaml_parse_file($file);
				}
				break;
			case 'json':
				$data = file_get_contents($file);

				if (false !== $data) {
					$data = json_decode($data, true);

					if (json_last_error() === JSON_ERROR_NONE) {
						$result = $data;
					}
				}

				break;
		}

		return isset($result) && is_array($result) ? $result : [];
	}


	/**
	 * 切换语言
	 * @access public
	 * @param string $langset 语言
	 * @return void
	 */
	public function switchLangSet(string $langset)
	{
		if (empty($langset)) {
			return;
		}

		$this->setLangSet($langset);

		// 加载系统语言包
		$this->load([
			ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $langset . '.php',
		]);
		// 加载系统语言包
		$files = glob(ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $langset . '.*');
		$this->load($files);
	}

	/**
	 * 设置当前语言
	 * @access public
	 * @param string $lang 语言
	 * @return void
	 */
	public function setLangSet(string $lang): void
	{
		$this->range = $lang;
	}

	/**
	 * 获取语言定义(不区分大小写)
	 * @access public
	 * @param string|null $name  语言变量
	 * @param array       $vars  变量替换
	 * @param string      $range 语言作用域
	 * @return mixed
	 */
	public function get(string $name = null, array $vars = [], string $range = '')
	{
		$range = $range ?: $this->range;

		if (!isset($this->lang[$range])) {
			$this->switchLangSet($range);
		}

		// 空参数返回所有定义
		if (is_null($name)) {
			return $this->lang[$range] ?? [];
		}

		$value = $this->lang[$range][strtolower($name)] ?? $name;

		// 变量解析
		if (!empty($vars) && is_array($vars)) {
			/**
			 * Notes:
			 * 为了检测的方便，数字索引的判断仅仅是参数数组的第一个元素的key为数字0
			 * 数字索引采用的是系统的 sprintf 函数替换，用法请参考 sprintf 函数
			 */
			if (key($vars) === 0) {
				// 数字索引解析
				array_unshift($vars, $value);
				$value = call_user_func_array('sprintf', $vars);
			} else {
				// 关联索引解析
				$replace = array_keys($vars);
				foreach ($replace as &$v) {
					$v = "{:{$v}}";
				}
				$value = str_replace($replace, $vars, $value);
			}
		}

		return $value;
	}

	/**
	 * 判断是否存在语言定义(不区分大小写)
	 * @access public
	 * @param string|null $name  语言变量
	 * @param string      $range 语言作用域
	 * @return bool
	 */
	public function has(string $name, string $range = ''): bool
	{
		$range = $range ?: $this->range;

		if ($this->config['allow_group'] && strpos($name, '.')) {
			[$name1, $name2] = explode('.', $name, 2);
			return isset($this->lang[$range][strtolower($name1)][$name2]);
		}

		return isset($this->lang[$range][strtolower($name)]);
	}

}
