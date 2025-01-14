<?php

/**
 * +----------------------------------------------------------------------
 * | 控制台内核
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

use Dfer\DfPhpCore\Modules\Statics\{Config, Mysql};
use Dfer\Tools\Common;

class Console extends Common
{
  /**
   * 初始化
   */
  public function run()
  {
    global $argc, $argv, $db;
    mb_internal_encoding('UTF-8');
    //当前时间
    define('TIMESTAMP', time());
    //项目根目录
    define('ROOT', dirname(__DIR__, 5));
    define('VERSION', file_get_contents(ROOT . DIRECTORY_SEPARATOR . 'version'));
    define('QUIET', in_array('-q', $argv));

    Config::init();
    // 开发模式开关（调试完之后关闭此开关，否则有泄露网站结构的风险）
    define('DEV', config('dev', 1));
    define('SERVER', config('server', 'localhost'));
    define('ACC', config('account', 'dfphp_dfer_site'));
    define('PWD', config('password', 'mMHBCAimbKKjPP67'));
    define('DATABASE', config('database', 'dfphp_dfer_site'));

    $db = Mysql::init();
    $this->init();
  }

  /**
   * 初始化
   * @param {Object} $var 变量
   */
  function init($var = null)
  {
    global $argc, $argv;

    $str = <<<'STR'
      DfPHP v{ver}
      用法:
      命令 [选项]

      选项:
      -q                          不显示任何信息

      内置:
      version                     查看DfPHP的当前版本
      dev:root                    将项目的核心文件发布至`df-php-root`组件库
      dev:core                    将项目里的`df-php-core`同步至组件库
      dev:tools                   将项目里的`tools`同步至组件库

      STR;
    // var_dump(config('console.'),VERSION, mb_internal_encoding());
    $config = config('console.');
    if (count($config) > 0) {
      $str .= PHP_EOL . "自定义:" . PHP_EOL;
    }
    foreach ($config as $key => $value) {
      // 右填充空格
      $key = str_pad($key, 28, ' ', STR_PAD_RIGHT);
      $remark = $value['remark'];
      $str .= "{$key}{$remark}" . PHP_EOL;
    }

    if ($argc == 1) {
      $this->print($this->str($str, ['ver' => VERSION]));
      return;
    }
    $command = $argv[1];
    switch ($command) {
      case 'version':
        $this->print(VERSION);
        break;
      case 'dev:root':
        $this->devRoot();
        break;
      case 'dev:core':
        $this->devCore();
        break;
      case 'dev:tools':
        $this->devTools();
        break;
      default:
        if (isset($config[$command])) {
          $class = $config[$command]['class'];
          new $class;
        } else
          $this->print("命令不存在");
        break;
    }
  }

  /**
   * 将框架里的最新内容同步到`df-php-root`
   * @param {Object} $var 变量
   */
  function devRoot($var = null)
  {
    $projectRootDir = ROOT.DIRECTORY_SEPARATOR;
    // 模块项目所在的目录，非开发者无法使用该功能
    $moduleRootDir = dirname(ROOT) . DIRECTORY_SEPARATOR . 'df-php-root' . DIRECTORY_SEPARATOR . 'root' . DIRECTORY_SEPARATOR;

    // 需要同步的目录
    $dir = [
      'areas',
      'data' . DIRECTORY_SEPARATOR . 'db',
      'data' . DIRECTORY_SEPARATOR . 'lang',
      'extend',
      'public' . DIRECTORY_SEPARATOR . 'node_modules',
      'public' . DIRECTORY_SEPARATOR . 'view',
      'public' . DIRECTORY_SEPARATOR . 'index.php',
      '.editorconfig',
      'df',
      'version'
    ];
    if (is_dir(dirname($moduleRootDir))) {
      $this->print($projectRootDir . ">>>" . $moduleRootDir . PHP_EOL);
      $this->print("////////////////////////////////////////////////// 文件删除 START //////////////////////////////////////////////////" . PHP_EOL);
      $this->deleteDir($moduleRootDir, QUIET);
      $this->print("//////////////////////////////////////////////////  文件删除 END  //////////////////////////////////////////////////" . PHP_EOL);
      sleep(1.5);
      $this->print(PHP_EOL);
      $this->print("////////////////////////////////////////////////// 文件复制 START //////////////////////////////////////////////////" . PHP_EOL);
      foreach ($dir as $key => $value) {
        $this->copy($projectRootDir . $value, $moduleRootDir . $value, QUIET);
      }
      $this->print("//////////////////////////////////////////////////  文件复制 END  //////////////////////////////////////////////////" . PHP_EOL);
      sleep(1.5);
      $this->print(PHP_EOL);
      $this->print("////////////////////////////////////////////////// 提交git START //////////////////////////////////////////////////" . PHP_EOL);
      system("cd ../df-php-root/ && publish.bat");
      $this->print("//////////////////////////////////////////////////  提交git END  //////////////////////////////////////////////////" . PHP_EOL);
    } else {
      $this->print("此功能为框架开发者使用");
    }
  }

  /**
   * 将框架里的最新内容同步到`df-php-core`
   * @param {Object} $var 变量
   */
  function devCore($var = null)
  {
    $projectModuleRootDir = ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'dfer' . DIRECTORY_SEPARATOR . 'df-php-core' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    // 模块项目所在的目录，非开发者无法使用该功能
    $moduleRootDir = dirname(ROOT) . DIRECTORY_SEPARATOR . 'df-php-core' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

    if (is_dir(dirname($moduleRootDir))) {
      $this->print($projectModuleRootDir . ">>>" . $moduleRootDir . PHP_EOL);
      $this->print("////////////////////////////////////////////////// 文件删除 START //////////////////////////////////////////////////" . PHP_EOL);
      $this->deleteDir($moduleRootDir, QUIET);
      $this->print("//////////////////////////////////////////////////  文件删除 END  //////////////////////////////////////////////////" . PHP_EOL);
      sleep(1.5);
      $this->print(PHP_EOL);
      $this->print("////////////////////////////////////////////////// 文件复制 START //////////////////////////////////////////////////" . PHP_EOL);
      $this->copy($projectModuleRootDir, $moduleRootDir, QUIET);
      $this->print("//////////////////////////////////////////////////  文件复制 END  //////////////////////////////////////////////////" . PHP_EOL);
      sleep(1.5);
      $this->print(PHP_EOL);
      $this->print("////////////////////////////////////////////////// 提交git START //////////////////////////////////////////////////" . PHP_EOL);
      system("cd ../df-php-core/ && publish.bat");
      $this->print("//////////////////////////////////////////////////  提交git END  //////////////////////////////////////////////////" . PHP_EOL);
    } else {
      $this->print("此功能为框架开发者使用");
    }
  }

  /**
   * 将框架里的最新内容同步到`tools`
   * @param {Object} $var 变量
   */
  function devTools($var = null)
  {
    // 组件在项目中的目录
    $projectModuleRootDir = ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'dfer' . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    // 模块项目所在的目录，非开发者无法使用该功能
    $moduleRootDir = dirname(ROOT) . DIRECTORY_SEPARATOR . 'dfer-tools' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

    if (is_dir(dirname($moduleRootDir))) {
      $this->print($projectModuleRootDir . ">>>" . $moduleRootDir . PHP_EOL);
      $this->print("////////////////////////////////////////////////// 文件删除 START //////////////////////////////////////////////////" . PHP_EOL);
      $this->deleteDir($moduleRootDir, QUIET);
      $this->print("//////////////////////////////////////////////////  文件删除 END  //////////////////////////////////////////////////" . PHP_EOL);
      sleep(1.5);
      $this->print(PHP_EOL);
      $this->print("////////////////////////////////////////////////// 文件复制 START //////////////////////////////////////////////////" . PHP_EOL);
      $this->copy($projectModuleRootDir, $moduleRootDir, QUIET);
      $this->print("//////////////////////////////////////////////////  文件复制 END  //////////////////////////////////////////////////" . PHP_EOL);
      sleep(1.5);
      $this->print(PHP_EOL);
      $this->print("////////////////////////////////////////////////// 提交git START //////////////////////////////////////////////////" . PHP_EOL);
      system("cd ../dfer-tools/ && publish.bat");
      $this->print("//////////////////////////////////////////////////  提交git END  //////////////////////////////////////////////////" . PHP_EOL);
    } else {
      $this->print("此功能为框架开发者使用");
    }
  }

  /**
   * 输出
   * @param {Object} $var 变量
   */
  function print($var = null)
  {
    if (!QUIET)
      echo $this->str($var);
  }
}
