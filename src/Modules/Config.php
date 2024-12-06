<?php

/**
 * +----------------------------------------------------------------------
 * | 配置文件类
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

namespace Dfer\DfPhpCore\Modules;

use Dfer\Tools\Common;

class Config extends Common
{
    /**
     * 配置参数
     * @var array
     */
    protected static $config = [];

    /**
     * 配置前缀
     * @var string
     */
    protected $prefix = 'web';

    protected $configExt = '.php';

    /**
     * 加载配置文件
     * @param {Object} $var 变量
     */
    public function init($var = null)
    {
        $path = ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
        // 自动读取配置文件
        if (is_dir($path . 'config')) {
            $dir = $path . 'config' . DIRECTORY_SEPARATOR;
        }

        $files = isset($dir) ? scandir($dir) : [];
        // var_dump($files);
        foreach ($files as $file) {
            if ('.' . pathinfo($file, PATHINFO_EXTENSION) === $this->configExt) {
                $this->load($dir . $file, pathinfo($file, PATHINFO_FILENAME));
            }
        }
    }

    /**
     * 加载配置文件（多种格式）
     * @access public
     * @param  string    $file 配置文件名
     * @param  string    $name 一级配置名
     * @return mixed
     */
    public function load($file, $name = '')
    {
        if (is_file($file)) {
            $filename = $file;
        }

        if (isset($filename)) {
            return $this->loadFile($filename, $name);
        }
        return $this->config;
    }

    protected function loadFile($file, $name)
    {
        $name = strtolower($name);
        $type = pathinfo($file, PATHINFO_EXTENSION);
        // var_dump($file, $name,$type);
        if ('php' == $type) {
            return $this->set(include $file, $name);
        }

        return $this->parse($file, $type, $name);
    }

    /**
     * 解析配置文件或内容
     * @access public
     * @param  string    $config 配置文件路径或内容
     * @param  string    $type 配置解析类型
     * @param  string    $name 配置名（如设置即表示二级配置）
     * @return mixed
     */
    public function parse($config, $type = '', $name = '')
    {
        if (is_file($config)) {
            $config = file_get_contents($config);
        }

        return $this->set(json_decode($config, true), $name);
    }

    /**
     * 设置配置参数 name为数组则为批量设置
     * @access public
     * @param  mixed         $value 配置值
     * @param  string|array  $name 配置参数名（支持三级配置 .号分割）
     * @return mixed
     */
    public function set($value, $name = null)
    {
        if (is_array($value)) {
            // 批量设置
            if (!empty($name)) {
                if (isset($this->config[$name])) {
                    $result = array_merge($this->config[$name], $value);
                } else {
                    $result = $value;
                }

                $this->config[$name] = $result;
            } else {
                $result = $this->config = array_merge($this->config, $value);
            }
        } else {
            // 为空直接返回 已有配置
            $result = $this->config;
        }
        // var_dump($this->config);
        return $result;
    }

    /**
     * 获取配置参数 为空则获取所有配置
     * @access public
     * @param  string    $name      配置参数名（支持多级配置 .号分割）
     * @param  mixed     $default   默认值
     * @return mixed
     */
    public function get($name = null, $default = null)
    {
        // var_dump($this->config,$name);
        if ($name && false === strpos($name, '.')) {
            $name = $this->prefix . '.' . $name;
        }

        // 无参数时获取所有
        if (empty($name)) {
            return $this->config;
        }

        // 以“.”结尾则属于一级配置名，直接获取获取一级配置
        if ('.' == substr($name, -1)) {
            $name = strtolower(substr($name, 0, -1));
            return $this->config[$name] ?? [];
        }

        $name = explode('.', $name);
        $name[0] = strtolower($name[0]);
        $config = $this->config;
        // 按.拆分成多维数组进行判断
        foreach ($name as $val) {
            // var_dump($val);
            if (isset($config[$val])) {
                $config = $config[$val];
            } else {
                return $default;
            }
        }

        return $config;
    }
}
