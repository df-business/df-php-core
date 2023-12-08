<?php
namespace Dfer\DfPhpCore\Modules;

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
 *                 ......    .!%$! ..        | AUTHOR: dfer
 *         ......        .;o*%*!  .          | EMAIL: df_business@qq.com
 *                .:;;o&***o;.   .           | QQ: 3504725309
 *        .;;!o&****&&o;:.    ..
 * +----------------------------------------------------------------------
 *
 */
class Web
{

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
								define('ROOT', dirname(__DIR__,5));
        // 网站运行目录
        define('WEB_ROOT', $_SERVER['DOCUMENT_ROOT']);
        //内核根目录
        define('DF_PHP_ROOT', ROOT . DIRECTORY_SEPARATOR. 'vendor/dfer/df-php-core/src/');
        // 默认模板
        define('THEME_HOMEPAGE', env('THEME_HOMEPAGE', 'homepage'));
        define('THEME_ADMIN', env('THEME_ADMIN', 'admin'));
        // 后台入口
        define('ADMIN_URL', env('ADMIN_URL', 'df'));
        // 开发模式开关（调试完之后关闭此开关，否则有泄露网站结构的风险）
        define('DEV', env('DEV', 1));
        define('SERVER', env('SERVER', 'localhost'));
        define('ACC', env('ACC', 'dfphp_dfer_site'));
        define('PWD', env('PWD', 'mMHBCAimbKKjPP67'));
        define('DATABASE', env('DATABASE', 'dfphp_dfer_site'));

        //email模块的开关
        define('EMAIL_ENABLE', false);
        // 当前框架的版本
        define('VERSION', file_get_contents(ROOT. DIRECTORY_SEPARATOR.'version'));
        //当前框架需要的最低php版本
        define('PHP_VERSION_MIN', env('PHP_VERSION_MIN', getComposerJson()));
        //seo优化模式
        define('SEO', env('SEO', 1));
        //PC页面、手机页面分离开关
        define('WAP_PAGE_ENABLE', env('WAP_PAGE_ENABLE', 1));
        // 3*24小时
        define('SESSION_EXPIRES', env('SESSION_EXPIRES', 3 * 24 * 3600));
        //设置文件上传的最大尺寸(byte)
        define('FILE_SIZE_MAX', env('FILE_SIZE_MAX', 1024 * 1024 * 100));

        // ssl状态
        define('SSL_STATE', !empty($_SERVER['HTTPS']));
        if (SSL_STATE) {
        	// 自动将页面元素的http升级为https,需要保证页面中所有资源都支持https访问
        	header("Content-Security-Policy: upgrade-insecure-requests");
        	define('SITE', 'https://' . $_SERVER['HTTP_HOST'] . '/');
        } else {
        	define('SITE', 'http://' . $_SERVER['HTTP_HOST'] . '/');
        }
        //当前页面完整url
        define('URL', htmlspecialchars_decode(SITE . 'index.php?' . htmlspecialchars($_SERVER['QUERY_STRING'])));
        // **********************  常量 END  **********************



        // ********************** 错误信息的控制 START **********************
        // http://www.w3school.com.cn/php/php_error.asp
        switch(DEV){
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

        global $db,$common, $files,$other,$_param, $_df;
								$common = new \Dfer\Tools\Common;
								$files = new \Dfer\Tools\Files;
								$other = new \Dfer\DfPhpCore\Modules\Other;

        $db=dbInit();
								
        $_param = $common->ihtmlspecialchars(array_merge($_GET, $_POST));
        $_df = [
        	'logo' => "https://oss.dfer.site/df_icon/81x81.png",
        	'author' => "谷雨陈",
        	'qq' => "3504725309",
        	'time' => $common->getTime(TIMESTAMP),
        	'admin' => boolval(showFirst('dt', ['key' => 'admin'])['val'])
        ];
        // **********************  框架初始化 END  **********************

								$this->index();
    }


				/**
				 * 入口文件
				 * @param {Object} $var 变量
				 **/
				function index($var = null)
				{
				    global $common, $files, $_df;
				    try {
				        df();
				        if (isset($_GET['f'])) {
				            $files->addFile($_GET['f']);
				        }

				        //初始页面
				        $src_string = isset($_GET['s']) ? $_GET['s'] : (SEO ? "index" : THEME_HOMEPAGE);
				        debug(sprintf("当前页面原始路径：%s", $src_string));

				        if (substr($src_string, -5) == ".html")
				            $src_string = str_replace(".html", "", $src_string);

				        $src = explode('/', $src_string);

				        //短路径
				        if (SEO && $src[0] != "df" && count($src) <= 2) {
				            $area_name = THEME_HOMEPAGE;
				            $ctrl_name = 'home';
				            $action_name = empty($src[0]) ? 'index' : $src[0];

				            $param = null;
				            if (isset($src[1])) {
				                $param_items = array_slice($src, 1);
				                $param = count($param_items) == 1 ? $param_items[0] : $param_items;
				            }
				        } else {
				            // 完整路径
				            $area_name = $common->unHump($src[0]) == ADMIN_URL ? THEME_ADMIN : $src[0];
				            $ctrl_name = empty($src[1]) ? 'home' : $src[1];
				            $action_name = empty($src[2]) ? 'index' : $src[2];

				            $param = null;
				            if (isset($src[3])) {
				                $param_items = array_slice($src, 3);
				                $param = count($param_items) == 1 ? $param_items[0] : $param_items;
				            }
				        }

				        $_df['area'] = $area_name;
				        $_df['ctrl'] = $ctrl_name;
				        $_df['action'] = $action_name;

				        $ctrl_name = ucwords($ctrl_name) . "Controller";
				        // 控制器方法同时支持下划线和驼峰
				        $action_name = $common->hump($action_name);
												$ctrl_path = "areas\\{$area_name}\\controller\\{$ctrl_name}";



				        if (DEV) {
				            class_exists($ctrl_path) or die("控制器不存在:{$ctrl_path}");
																$controller=new $ctrl_path;
				            method_exists($controller, $action_name) or die(sprintf('文件:%s<br>控制器、方法定义出错:%s %s', $ctrl_path, json_encode($_GET), json_encode($src)));
				        } else {
				            class_exists($ctrl_path) or header(sprintf("Location: %s/../404.html", VIEW_ASSETS));
				            $controller=new $ctrl_path;
				            method_exists($controller, $action_name) or header(sprintf("Location: %s/../404.html", VIEW_ASSETS));
				        }
				        $controller->$action_name($param);
				    } catch (Exception $e) {
				        if (DEV)
				            var_dump($e);
				        else
				            header(sprintf("Location: %s/../404.html", VIEW_ASSETS));
				    }
				}

  }
