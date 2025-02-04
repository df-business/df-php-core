<?php

/**
 * +----------------------------------------------------------------------
 * | 网页内核
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

use Dfer\DfPhpCore\Modules\Statics\{Mysql, Config};
use Dfer\Tools\{Common};

class Web extends Common
{
    /**
     * 容器绑定标识
     * @var array
     */
    protected $bind = [
        'mysql' => Mysql::class,
        'config' => Config::class
    ];

    /**
     * 初始化
     */
    public function run()
    {
        //当前时间
        define('TIMESTAMP', time());
        //访问者ip
        define('IP', $_SERVER['REMOTE_ADDR']);
        //项目根目录
        define('ROOT', dirname(__DIR__, 5));
        // 网站运行目录
        define('WEB_ROOT', $_SERVER['DOCUMENT_ROOT']);
        //内核根目录
        define('DF_PHP_ROOT', ROOT . DIRECTORY_SEPARATOR . 'vendor/dfer/df-php-core/src/');

        // 配置参数初始化
        $this->config::init();

        // 默认模板
        define('DEFAULT_ADMIN', 'admin');
        define('THEME_HOMEPAGE', config('theme_homepage', 'homepage'));
        define('THEME_ADMIN', config('theme_admin', DEFAULT_ADMIN));
        define('DEFAULT_ADMIN_ASSETS', "/view/" . DEFAULT_ADMIN . "/public/assets");
        // 后台入口
        define('ADMIN_URL', config('admin_url', 'df'));
        // 开发模式开关（调试完之后关闭此开关，否则有泄露网站结构的风险）
        define('DEV', config('dev', 1));
        define('SERVER', config('server', 'localhost'));
        define('ACC', config('account', 'dfphp_dfer_site'));
        define('PWD', config('password', 'mMHBCAimbKKjPP67'));
        define('DATABASE', config('database', 'dfphp_dfer_site'));

        //email模块的开关
        define('EMAIL_ENABLE', false);
        // 自动检测语言
        define('LANG_DETECT', config('lang_detect', 0));
        // 当前框架的版本
        define('VERSION', file_get_contents(ROOT . DIRECTORY_SEPARATOR . 'version'));
        //当前框架需要的最低php版本
        define('PHP_VERSION_MIN', config('php_version_min', '7.3'));
        //seo优化模式
        define('SEO', config('SEO', 0));
        //PC页面、手机页面分离开关
        define('WAP_PAGE_ENABLE', config('wap_page_enable', 1));
        // 3*24小时
        define('SESSION_EXPIRES', config('session_expires', 3 * 24 * 3600));
        //设置文件上传的最大尺寸(byte)
        define('FILE_SIZE_MAX', config('file_size_max', 1024 * 1024 * 100));

        // ssl启用
        define('SSL_ACTIVE', !empty($_SERVER['HTTPS']) || (isset($_SERVER['HTTP_X_CLIENT_SCHEME']) && $_SERVER['HTTP_X_CLIENT_SCHEME'] == 'https'));
        if (SSL_ACTIVE) {
            // 自动将页面元素的http升级为https,需要保证页面中所有资源都支持https访问
            header("Content-Security-Policy: upgrade-insecure-requests");
            define('SITE', 'https://' . $_SERVER['HTTP_HOST']);
        } else {
            define('SITE', 'http://' . $_SERVER['HTTP_HOST']);
        }
        //当前页面完整url
        define('URL_ORIGIN', htmlspecialchars_decode(SITE . 'index.php?' . htmlspecialchars($_SERVER['QUERY_STRING'])));
        define('URL', htmlspecialchars_decode(SITE . htmlspecialchars($_SERVER['REQUEST_URI'])));
        // **********************  常量 END  **********************

        // ********************** 错误信息的控制 START **********************
        // http://www.w3school.com.cn/php/php_error.asp
        switch (DEV) {
            case true:
                //屏蔽提示和警告信息
                ini_set('display_errors', '1');
                error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
                break;
            case false:
                //屏蔽所有错误信息,主要用于美化界面，治标不治本
                error_reporting(0);
                break;
            default:
                //显示所有错误
                ini_set('display_errors', '1');
                error_reporting(E_ALL);
                break;
        }

        // **********************  错误信息的控制 END  **********************


        // ********************** 框架初始化 START **********************

        //使html内容可以擦除
        ob_start();
        //开启缓存
        session_start();
        //设置时区
        date_default_timezone_set("PRC");
        //编码为utf-8
        header("Content-Type:text/html; charset=utf-8");
        //解除跨域限制
        header("Access-Control-Allow-Origin: *");

        global $db, $_site, $_param;
        $db = $this->mysql::init();
        $_param = param();
        $_site = [
            'logo' => "https://oss.dfer.site/df_icon/81x81.png",
            'author' => "谷雨陈",
            'qq' => "3504725309",
            'time' => $this->getTime(TIMESTAMP)
        ];
        // **********************  框架初始化 END  **********************
        $this->index();
    }

    /**
     * 入口文件
     * @param {Object} $var 变量
     */
    function index($var = null)
    {
        global $_param;
        try {
            $src_string = get('s') ?? (SEO ? "index" : THEME_HOMEPAGE);
            debug(sprintf("当前页面原始路径：%s", $src_string));

            if (substr($src_string, -5) == ".html")
                $src_string = str_replace(".html", "", $src_string);

            $src_arr = explode('/', $src_string);

            //短路径。只影响前端页面
            if (SEO && !in_array($src_arr[0], [ADMIN_URL, THEME_ADMIN])) {
                $area_name = THEME_HOMEPAGE;
                $ctrl_name = 'home';
                $action_name = $src_arr[0] ?? $src_arr[0] ?: 'index';

                $param = null;
                if (isset($src_arr[1])) {
                    $param_items = array_slice($src_arr, 1);
                    $param = count($param_items) == 1 ? $param_items[0] : $param_items;
                }
            } else {
                // 完整路径
                $area_name = $this->unHump($src_arr[0]) == ADMIN_URL ? THEME_ADMIN : $src_arr[0];
                $ctrl_name = $src_arr[1] ?? $src_arr[1] ?: 'home';
                $action_name = $src_arr[2] ?? $src_arr[2] ?: 'index';

                // action_name之后的数据全部作为方法的参数进行传递
                $param = null;
                if (isset($src_arr[3])) {
                    $param_items = array_slice($src_arr, 3);
                    $param = count($param_items) == 1 ? $param_items[0] : $param_items;
                }
            }

            $_param['area'] = $area_name;
            $_param['ctrl'] = $ctrl_name;
            $_param['action'] = $action_name;
            $_param['param'] = $param;

            debug($_param, $param);

            $ctrl_name = ucwords($ctrl_name) . "Controller";
            // 控制器方法同时支持下划线和驼峰
            $action_name = $this->hump($action_name);
            $ctrl_path = "areas\\{$area_name}\\controller\\{$ctrl_name}";

            if (DEV) {
                class_exists($ctrl_path) or
                    die(<<<STR
                控制器不存在<br/>
                控制器::{$ctrl_path}<br/>
                STR);
                $controller = new $ctrl_path;
                method_exists($controller, $action_name) or
                    die(str(
                            <<<STR
                方法不存在<br/>
                参数:{0}<br/>
                控制器:{1}<br/>
                方法: {2}<br/>
                STR,
                            [json_encode($_GET), $ctrl_path, $action_name]
                        ));
            } else {
                class_exists($ctrl_path) or include_once view('404', true);
                $controller = new $ctrl_path;
                method_exists($controller, $action_name) or include_once view('404', true);
            }
            $controller->$action_name($param);
        } catch (Exception $e) {
            if (DEV)
                echo str($e);
            else
                include_once view('404', true);
        }
    }

    public function __get($name)
    {
        $bind = $this->bind[$name];
        return $bind;
    }
}
