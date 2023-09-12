<!--
create.php
创建数据库

Created by dfer on 2017-10-23.
Copyright 2017 dfer. All rights reserved.
-->
<?php
$create = true;
require "share.php";
DEV or die("<br>当前没有处于开发模式");

//————————————————————————————————————————创建数据库
//————————————————————核心库
//后台登陆账号，不要删
$sql[] = "CREATE TABLE `df`
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
nm varchar(50) DEFAULT 'df' COMMENT '账号名',
pw varchar(50) DEFAULT 'df',
pic varchar(100) DEFAULT '/favicon.png',
role int NOT NULL DEFAULT 0 COMMENT '权限',
createtime datetime COMMENT '创建时间',
lastlogintime datetime COMMENT '上次访问'
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
";
//账号权限，不要删
$sql[] = "CREATE TABLE `roles`
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
nm varchar(50) DEFAULT '普通用户' COMMENT '权限名',
roles varchar(100) DEFAULT '1|2' COMMENT '权限内容'
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
";
//常用数据，不要删
$sql[] = "CREATE TABLE `dt`
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
`key` varchar(15) DEFAULT '' COMMENT '参数名',
`val` varchar(150) DEFAULT '0' COMMENT '值',
subs varchar(100) DEFAULT '' COMMENT '描述'
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
";

//后台菜单，不要删
$sql[] = "CREATE TABLE menu
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
title varchar(50) COMMENT '标题',
src varchar(100) COMMENT '路径',
type varchar(30) COMMENT '类型',
parent int DEFAULT 0 COMMENT '上级Id',
orderNum int DEFAULT 0 COMMENT '排序编号'
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
";
//html页面，不要删
$sql[] = "CREATE TABLE html
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
fileN varchar(50) COMMENT 'htm文件名',
src varchar(100) COMMENT '动态路径',
comment varchar(30) COMMENT '备注'
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
";
//服务器缓存,不要删
$sql[] = "CREATE TABLE cache
(
 `key` varchar(50) NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY (`key`)
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

//日志，不要删
$sql[] = "CREATE TABLE logs
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
str longtext COMMENT '记录内容',
time datetime
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
";
//————————————————————基础库

//用户信息收集
$sql[] = "CREATE TABLE home_user_info
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
ip varchar(55) DEFAULT '' COMMENT '访问者ip',
browser varchar(500) DEFAULT '' COMMENT '访问者使用的浏览器',
hits int DEFAULT 0 COMMENT '访问总次数',
first_time datetime COMMENT '访问者首次访问的时间',
time datetime COMMENT '访问者最近访问的时间'
)ENGINE=InnoDB COMMENT='用户信息收集' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

//主页布局
$sql[] = "CREATE TABLE home_layout
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
title varchar(100) DEFAULT '' COMMENT '',
keywords varchar(100) DEFAULT '' COMMENT '关键字',
description varchar(100) DEFAULT '' COMMENT '网页简介',
Inscribe varchar(300) DEFAULT '' COMMENT '',
img1 varchar(100) DEFAULT '/assets/img/bg.jpg' COMMENT '背景图像',
color varchar(10) DEFAULT '' COMMENT '主体字体颜色',
musicPlay tinyint DEFAULT 0 COMMENT '音乐自动播放',
`sceneId` int DEFAULT 0 COMMENT '模板id'
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

//背景图片列表
$sql[] = "CREATE TABLE home_layout_img
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
title varchar(100) DEFAULT '' COMMENT '',
img varchar(100) DEFAULT '' COMMENT '背景图像'
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

//栏目
$sql[] = "CREATE TABLE home_column
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
menu varchar(100) DEFAULT '' COMMENT '',
title varchar(100) DEFAULT '' COMMENT '',
`describe` varchar(100) DEFAULT '' COMMENT '',
content longtext COMMENT '内容'
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

//链接
$sql[] = "CREATE TABLE home_link
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
title varchar(100) DEFAULT '' COMMENT '',
`src` varchar(100) DEFAULT '' COMMENT ''
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

//音乐
$sql[] = "CREATE TABLE home_music
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
title varchar(100) DEFAULT '' COMMENT '',
`src` varchar(100) DEFAULT '' COMMENT ''
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

//留言
$sql[] = "CREATE TABLE message
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
name varchar(100) DEFAULT '' COMMENT '',
E_mail varchar(100) DEFAULT '' COMMENT '',
`content` longtext COMMENT '内容',
`status` tinyint DEFAULT 0 COMMENT '阅读状态',
time varchar(50)
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

//记事本
$sql[] = "CREATE TABLE notepad
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
title varchar(50) COMMENT '标题',
content longtext COMMENT '内容',
time varchar(50)
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
";

//站点介绍
$sql[] = "CREATE TABLE `column`
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
menu varchar(50),
title varchar(55),
pic varchar(100),
content longtext
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
";

//demo
$sql[] = "CREATE TABLE `demo`
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
title varchar(55),
type int,
file varchar(150),
pic varchar(150),
content longtext,
msg longtext,
time datetime
)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
";

//————————————————————拓展库

$sql[] = "CREATE TABLE `test`
(
Id int NOT NULL AUTO_INCREMENT,
PRIMARY KEY(Id),
title varchar(50) COMMENT '标题'
)ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
";



/*
 * 存在数据库是前提条件
 * 数据库不存在就创建数据库
 *
 * 先创建库，然后自我刷新，创建表
 */
if (!mysqli_select_db($con, $database)) {
    if (mysqli_query($con, "CREATE DATABASE {$database}")) {
        echo("数据库 {$database} 创建成功");
        header('refresh:0;url=create.php');
        die();
    } else {
        echo "{$database} 创建失败: " . mysqli_error($con);
    }
}
echo "<br>";

//创建表
$num = 0;
foreach ($sql as $val) {
    $num++;
    echo $num . ".";
    $str = explode("(", $val);
    $str = $str[0];
    if ($db -> query($val)) {
        echo $str . " [成功]";
    } else {
        echo $str . " [失败: " . $db -> error."]";
    }
    echo "<br>";
}

//————————————————————————————————————————添加初始数据
//添加登陆账号
//echo $db->query("SELECT * FROM `df`")->fetch_array()[1];   //读取首条数据
$query = $db -> query("SELECT COUNT(*) AS count FROM `df`") -> fetch_array();
if ($query[0] < 1) {
    if ($db -> query("insert into `df`(nm,pw,pic) values('df','df','/favicon.png')")) {
        echo "添加数据 [df] 成功";
    } else {
        echo "添加数据 [df] 失败";
    }
} else {
				echo "数据 [df] 已存在";
}
echo '<br>';
//添加账号权限
$query = $db -> query("SELECT COUNT(*) AS count FROM `roles`") -> fetch_array();
if ($query[0] < 1) {
    if ($db -> query("insert into `roles`(nm,roles) values('超级用户',''),('普通用户','1|2|7|10|15|16|')")) {
        echo "添加数据 [roles] 成功";
    } else {
        echo "添加数据 [roles] 失败";
    }
} else {
    echo "数据 [roles] 已存在";
}
echo '<br>';
//添加默认布局
$query = $db -> query("SELECT COUNT(*) AS count FROM `home_layout`") -> fetch_array();
if ($query[0] < 1) {
    if ($db -> query("insert into `home_layout`(`title`,keywords,description,Inscribe,img1,color) values('DfPHP','DfPHP,轻量级php框架','DfPHP——简洁的php框架','by Df','/assets/img/bg.jpg','#f0f4e3')")) {
        echo "添加数据 [home_layout] 成功";
    } else {
        echo "添加数据 [home_layout] 失败";
    }
} else {
    echo "数据 [home_layout] 已存在";
}
echo '<br>';

//添加默认栏目
$query = $db -> query("SELECT COUNT(*) AS count FROM `home_column`") -> fetch_array();
if ($query[0] < 1) {
    if ($db -> query('INSERT INTO `home_column` (`Id`, `menu`, `title`, `describe`, `content`) VALUES
	(1, "", "关键字说明", "", "<p><span style="white-space: nowrap;">//布局</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;df-html/&gt;</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;df-header/&gt;</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;df-body/&gt;</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;df-footer/&gt;&nbsp;</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;df-header&gt;&lt;/df-header&gt;</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;df-body&gt;&lt;/df-body&gt;</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;df-footer&gt;&lt;/df-footer&gt;</span></p><p><span style="white-space: nowrap;">&nbsp;</span></p><p><span style="white-space: nowrap;">&nbsp;//打印参数</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;df-print value=""&gt;</span></p><p><span style="white-space: nowrap;">&nbsp;!!$str!!</span></p><p><span style="white-space: nowrap;">&nbsp;</span></p><p><span style="white-space: nowrap;">&nbsp;</span></p><p><span style="white-space: nowrap;">&nbsp;//执行php代码</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;df-code&gt;</span></p><p><span style="white-space: nowrap;">&nbsp;!{}!</span></p><p><span style="white-space: nowrap;">&nbsp;</span></p><p><span style="white-space: nowrap;">&nbsp;//遍历数组，来循环显示多条数据</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;df-each $0&gt;</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;df-val value=""/&gt;</span></p><p><span style="white-space: nowrap;">&nbsp;!``</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;/df-each&gt;</span></p><p><span style="white-space: nowrap;">&nbsp;</span></p><p><span style="white-space: nowrap;">//这里放关键字，防止整理代码格式的时候关键字被破坏</span></p><p><span style="white-space: nowrap;">/*d<span style="white-space:pre">	</span></span></p><p><span style="white-space: nowrap;">d*/</span></p><p><span style="white-space: nowrap;">&nbsp;</span></p><p><span style="white-space: nowrap;">&nbsp;//if语句</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;df-if $0&gt;</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;df-elif $1&gt;</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;df-else&gt;</span></p><p><span style="white-space: nowrap;">&nbsp;&lt;/df-if&gt;</span></p><p><span style="white-space: nowrap;">&nbsp;</span></p><p><span style="white-space: nowrap;">&nbsp;!{if true}</span></p><p><span style="white-space: nowrap;">&nbsp;!{elif false}</span></p><p><span style="white-space: nowrap;">&nbsp;!{else}</span></p><p><span style="white-space: nowrap;">&nbsp;!{/else}</span></p><p><br></p>"),
	(2, "", "数据库操作", "", "<p><span style=\"white-space: nowrap;\">#查询#</span></p><p><span style=\"white-space: nowrap;\">//有多行就输出数组，否则返回单个list（有些情况必须返回数组，就添加order）</span></p><p><span style=\"white-space: nowrap;\">show(\"df\",1,\"type\",\" \");&nbsp; &nbsp;&nbsp;</span></p><p><span style=\"white-space: nowrap;\">// 根据字符串进行查询</span></p><p><span style=\"white-space: nowrap;\">show(\"df\",\"谷雨光影\",\"subs\");&nbsp;</span></p><p><span style=\"white-space: nowrap;\">// 按id降序输出全表&nbsp;&nbsp;</span></p><p><span style=\"white-space: nowrap;\">show(\"df\",-1,\"id\",\"desc\");<span style=\"white-space:pre\">	</span></span></p><p><span style=\"white-space: nowrap;\">//输出type为1的特定数目的数据</span></p><p><span style=\"white-space: nowrap;\">show(\"df\",1,\"type\",\"limit 0,5\");<span style=\"white-space:pre\">	</span></span></p><p><span style=\"white-space: nowrap;\">//输出type为1的数据并进行排序</span></p><p><span style=\"white-space: nowrap;\">show(\"df\",1,\"type\",\"order by id desc\");</span></p><p><span style=\"white-space: nowrap;\">//执行sql语句<span style=\"white-space:pre\">	</span></span></p><p><span style=\"white-space: nowrap;\">show(\"select * from df\",0);<span style=\"white-space:pre\">	</span></span></p><p><span style=\"white-space: nowrap;\">//按条件输出全表</span></p><p><span style=\"white-space: nowrap;\">show(\"menu\",$param,\"parent\",\"order by oderNum desc\");</span></p><p><span style=\"white-space: nowrap;\">//分页查询(页数,行数)</span></p><p><span style=\"white-space: nowrap;\">show_page(self::$db_d,$page,$rows);</span></p><p><span style=\"white-space: nowrap;\"><br></span></p><p><span style=\"white-space: nowrap;\">##新增、修改##</span></p><p><span style=\"white-space: nowrap;\">//新增数据，之后不进行任何操作</span></p><p><span style=\"white-space: nowrap;\">update(\"df\",$arr)<span style=\"white-space:pre\">		</span></span></p><p><span style=\"white-space: nowrap;\">//根据id新增、修改数据，之后进行页面跳转</span></p><p><span style=\"white-space: nowrap;\">update(self::$db_hc,$dt,$id,(\"homepage/column/\".self::$db_hc));<span style=\"white-space:pre\">	</span></span></p><p><span style=\"white-space: nowrap;\"><br></span></p><p><span style=\"white-space: nowrap;\"><br></span></p><p><span style=\"white-space: nowrap;\">##删除##</span></p><p><span style=\"white-space: nowrap;\">//根据id进行删除</span></p><p><span style=\"white-space: nowrap;\">del(\"db\",3);</span></p><p><span style=\"white-space: nowrap;\">//清空表</span></p><p><span style=\"white-space: nowrap;\">clear(\"db\")</span></p><p><br></p>"),
	(3, "", "框架介绍", "", "<p>-由Df打造的php版的Mvc框架，结构简洁，使用方便</p><p>-可以在此框架的基础上开发出各种各样的网站</p><p>-有很好的拓展性，可以不断增加新的功能</p><p>-由df提供技术支持</p><p>-此项目将不断完善</p><p>-工作QQ：3504725309&nbsp; &nbsp; &nbsp;&nbsp;</p><p>-个人网站：www.dfer.top</p><p>-论坛：forum.dfer.top&nbsp;</p><p>-QQ群：76673820</p><p>&nbsp;</p><p><br></p>");
')) {
        echo "添加数据 [home_column] 成功";
    } else {
        echo "添加数据 [home_column] 失败";
    }
} else {
    echo "数据 [home_column] 已存在";
}
echo '<br>';
//添加通用参数
$query = $db -> query("SELECT COUNT(*) AS count FROM `dt`") -> fetch_array();
if ($query[0] < 1) {
    if ($db -> query("insert into `dt`(`key`,val,subs) values('hits','0','用户访问量'),('admin','0','开启超级权限')")) {
        echo "添加数据 [dt] 成功";
    } else {
        echo "添加数据 [dt] 失败";
    }
} else {
    echo "数据 [dt] 已存在";
}
echo '<br>';
//添加静态页面
$query = $db -> query("SELECT COUNT(*) AS count FROM `html`") -> fetch_array();
if ($query[0] < 1) {
    if ($db -> query("insert into `html` (`fileN`,`src`) values('index','homepage/home/')")) {
        echo "添加数据 [html] 成功";
    } else {
        echo "添加数据 [html] 失败";
    }
} else {
    echo "数据 [html] 已存在";
}
echo '<br>';

//添加基础菜单
$query = $db -> query("SELECT COUNT(*) AS count FROM `menu`") -> fetch_array();
if ($query[0] < 1) {
    if ($db -> query("INSERT INTO `menu` (`title`, `src`, `type`, `parent`, `orderNum`) VALUES
('动态首页', 'homepage%2Fhome%2F', 'home', 0, 0),
('主页管理', '', 'folder', 0, 1),
('用户管理', '', 'user', 0, 2),
('生成静态页面', '', 'folder', 0, 8888),
('记事本', 'admin%2Fhome%2Fnotepad', 'book', 0, 100),
('关于此站点', 'admin%2Fhome%2Fcolumn	', 'info', 0, 110),
('刷新数据', 'js%3Arefresh_data%28%29%3B', 'refresh', 0, 120),
('装载数据', 'url%3A%2Fdata%2Fcreate.php', 'save', 0, 130),
('框架信息', 'admin%2Fhome%2Finfo', 'info', 0, 140),
('菜单', 'admin%2Fhome%2Fmenu', 'lock', 0, 150),
('日志', 'admin%2Fhome%2Flog', 'history', 0, 160),
('使用说明', 'admin%2Fhome%2Freadme', 'bug', 0, 170),

('布局', 'homepage%2Fcolumn%2Fhome_layout%2F1', 'file', 2, 0),
('栏目管理', 'homepage%2Fcolumn%2Fhome_column', 'file', 2, 0),
('链接管理', 'homepage%2Fcolumn%2Fhome_link', 'link', 2, 0),
('音乐管理', 'homepage%2Fcolumn%2Fhome_music', 'music', 2, 0),
('留言管理', 'homepage%2Fcolumn%2Fmessage+', 'comments', 2, 0),

('列表', 'admin%2Fhome%2Fdf', 'file', 3, 0),
('权限', 'admin%2Fhome%2Froles', 'file', 3, 0),
('访问者信息', 'admin%2Fhome%2Fguests', 'file', 3, 0),

('查看字体', 'url%3A%2Fmodules%2Ffont-spider%2Ffont.html', 'file', 4, 8880),
('页面管理', 'admin%2Fhome%2Fhtml', 'file', 4, 8881),
('生成', 'admin%2Fhome%2FcreateStaticPage', 'file', 4, 8882)
;")) {
        echo "添加数据 [menu] 成功";
    } else {
        echo "添加数据 [menu] 失败";
    }
} else {
    echo "数据 [menu] 已存在";
}
echo '<br>';

//数据结构更新
$sql_update = "ALTER TABLE `sp_club_info` ADD `content` longtext;";
if (!empty($sql_update)) {
    if ($db -> query($sql_update)) {
        echo "数据结构 更新成功";
    } else {
        echo "数据结构 更新失败";
    }
} else {
    echo "数据结构 不需要更新";
}
echo '<br>';

$db -> close();

echo("<br> <a target='' href='/'>进入主页</a><br> <a href='javascript:location.reload()'>刷新...</a>");
?>
