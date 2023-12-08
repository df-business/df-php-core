<?php
namespace Dfer\DfPhpCore\Modules;

/**
 * +----------------------------------------------------------------------
 * | 其他方法
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
class Other
{
    /**
     *
     * 验证登陆
     * ses里保存了用户的id、nm、pw
     * @param {Object} $type	0 跳转 1 获取id
     */
    public function verifyLogin($type = 0)
    {
        global $common;

        $login = getSession(\Enum::sesName);

        if (!empty($login)) {
            $id = $login[0];
            $nm = $common->hexToStr($login[1]);
            $pw = $common->hexToStr($login[2]);
            if ($type == 'all') {
                return array($id, $nm, $pw);
            }
            $user = showFirst('df', ['nm' => $nm]);

            if ($user['pw'] == $pw) {
                if ($type) {
                    //						var_dump($user['pw'] == $pw);die();
                    return $id;
                } else {
                    toUrl('admin/home/index');
                }
            }
        } else {
            if ($type) {
                toUrl('admin/login/index');
            }
            //header("location:?A=admin&c=login");
        }
    }

    /**
     * 将文件保存在根目录下
     * eg:
     * $this->log("{$url}<br />");
     * $this->log(json_encode($this->message)."<br />",7);    //写入数组
     * @param {Object} $str
     * @param {Object} $file
     */
    public function log($str, $file = "log")
    {
        $myfile = fopen(ROOT . "{$file}.htm", "w") or die("Unable to open file!");
        fwrite($myfile, $str);
        fclose($myfile);
    }

    /**
     * wx公众号状态
     * id不为空就设置缓存
     * id为空，有缓存就读取缓存，没有就读取wx第一条数据
     * @param {Object} $id
     */
    public function wxAc($id = '')
    {
        if (isset($_GET['wx_id'])) {
            return $_GET['wx_id'];
        }
        if ($id != '') {
            setSession('wx', $id);
            return $id;
        }
        $id = getSession('wx');
        if ($id == "") { //缓存不存在就读数据库
            $dt = show("wx", -1);
            $rt = $dt[0][0];
        } else { //存在就直接返回
            $rt = $id;
        }
        return $rt;
    }

    /**
     * 收集用户信息
     */
    public function colUserInfo()
    {
        global $_df;
        $db = 'home_user_info';
        $user = showFirst($db, ['ip' => IP]);
        if ($user) {
            $dt = array('browser' => $_SERVER['HTTP_USER_AGENT']??null, 'hits' => $user['hits'] + 1, 'time' => $_df['time']);
            update($db, $dt, $user['id']);
        } else {
            $dt = array('ip' => IP, 'browser' => $_SERVER['HTTP_USER_AGENT'], 'hits' => 0, 'first_time' => $_df['time'], 'time' => $_df['time']);
            update($db, $dt);
        }
    }

    /**
     * 发起zfb支付
     * @param {Object} $subject	订单名称
     * @param {Object} $total_amount	付款金额
     * @param {Object} $body	商品描述
     * @param {Object} $config_url	支付对象
     * @param {Object} $para	控制回调页面显示不同的内容
     */
    public function pay($subject, $total_amount, $body, $config_url, $para = 0)
    {
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = sprintf("Df-%s-%s-%s", TIMESTAMP, rand(), IP);
        setSession('dfOrder', $out_trade_no);
        $config_url = ROOT . sprintf("/module/alipay/%s.php", $config_url);
        $pay_url = ROOT . '/module/alipay/pagepay/pagepay.php';
        require $pay_url;
    }



	/**
	 * 创建表
	 * @param {Object} $con 数据库连接对象
	 * @param {Object} $database 数据库名称
	 **/
	public function createDb($db)
	{

		echo "###################################### 创建表 START ######################################";
		echo "<br />".PHP_EOL;


  // ********************** 核心库 START **********************

		//后台登陆账号，不要删
		$sql[] = "CREATE TABLE `df`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nm` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'df' COMMENT '账号名',
  `pw` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'df',
  `pic` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '/favicon.png',
  `role` int(11) NOT NULL DEFAULT 0 COMMENT '权限',
  `create_time` datetime NULL DEFAULT NULL COMMENT '创建时间',
  `last_login_time` datetime NULL DEFAULT NULL COMMENT '上次访问',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;
		";
		//账号权限，不要删
		$sql[] = "CREATE TABLE `roles`
		(
		id int NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(id),
		nm varchar(50) DEFAULT '普通用户' COMMENT '权限名',
		roles varchar(100) DEFAULT '1|2' COMMENT '权限内容'
		)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
		";
		//常用数据，不要删
		$sql[] = "CREATE TABLE `dt`
		(
		id int NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(id),
		`key` varchar(15) DEFAULT '' COMMENT '参数名',
		`val` varchar(150) DEFAULT '0' COMMENT '值',
		subs varchar(100) DEFAULT '' COMMENT '描述'
		)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
		";

		//后台菜单，不要删
		$sql[] = "CREATE TABLE menu
		(
		id int NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(id),
		title varchar(50) COMMENT '标题',
		src varchar(100) COMMENT '路径',
		`type` varchar(30) COMMENT '类型',
		parent int DEFAULT 0 COMMENT '上级id',
		order_num int DEFAULT 0 COMMENT '排序编号'
		)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
		";

		//html页面，不要删
		$sql[] = "CREATE TABLE html
		(
		id int NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(id),
		file_n varchar(50) COMMENT 'htm文件名',
		src varchar(100) COMMENT '动态路径',
		comment varchar(30) COMMENT '备注'
		)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
		";
		//服务器缓存,不要删
		$sql[] = "CREATE TABLE `cache`  (
  `key` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `value` longtext CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`key`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;";

		//日志，不要删
		$sql[] = "CREATE TABLE logs
		(
		id int NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(id),
		str longtext COMMENT '记录内容',
		time datetime
		)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
		";
		// **********************  核心库 END  **********************

		// ********************** 基础库 START **********************


		//用户信息收集
		$sql[] = "CREATE TABLE home_user_info
		(
		id int NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(id),
		ip varchar(55) DEFAULT '' COMMENT '访问者ip',
		browser varchar(500) DEFAULT '' COMMENT '访问者使用的浏览器',
		hits int DEFAULT 0 COMMENT '访问总次数',
		first_time datetime COMMENT '访问者首次访问的时间',
		time datetime COMMENT '访问者最近访问的时间'
		)ENGINE=InnoDB COMMENT='用户信息收集' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

		//主页布局
		$sql[] = "CREATE TABLE `home_layout`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
  `keywords` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '关键字',
  `description` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '网页简介',
  `inscribe` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '',
  `img1` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '背景图像',
  `color` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '主体字体颜色',
  `music_play` tinyint(4) NULL DEFAULT 0 COMMENT '音乐自动播放',
  `scene_id` int(11) NULL DEFAULT 0 COMMENT '模板id',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;";

		//背景图片列表
		$sql[] = "CREATE TABLE home_layout_img
		(
		id int NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(id),
		title varchar(100) DEFAULT '' COMMENT '',
		img varchar(100) DEFAULT '' COMMENT '背景图像'
		)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

		//栏目
		$sql[] = "CREATE TABLE home_column
		(
		id int NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(id),
		menu varchar(100) DEFAULT '' COMMENT '',
		title varchar(100) DEFAULT '' COMMENT '',
		`describe` varchar(100) DEFAULT '' COMMENT '',
		content longtext COMMENT '内容'
		)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

		//链接
		$sql[] = "CREATE TABLE home_link
		(
		id int NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(id),
		title varchar(100) DEFAULT '' COMMENT '',
		`src` varchar(100) DEFAULT '' COMMENT ''
		)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

		//音乐
		$sql[] = "CREATE TABLE home_music
		(
		id int NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(id),
		title varchar(100) DEFAULT '' COMMENT '',
		`src` varchar(100) DEFAULT '' COMMENT ''
		)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

		//留言
		$sql[] = "CREATE TABLE message
		(
		id int NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(id),
		name varchar(100) DEFAULT '' COMMENT '',
		e_mail varchar(100) DEFAULT '' COMMENT '',
		`content` longtext COMMENT '内容',
		`status` tinyint DEFAULT 0 COMMENT '阅读状态',
		time varchar(50)
		)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

		//记事本
		$sql[] = "CREATE TABLE notepad
		(
		id int NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(id),
		title varchar(50) COMMENT '标题',
		content longtext COMMENT '内容',
		time varchar(50)
		)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
		";

		//站点介绍
		$sql[] = "CREATE TABLE `column`
		(
		id int NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(id),
		menu varchar(50),
		title varchar(55),
		pic varchar(100),
		content longtext
		)DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
		";
		// **********************  基础库 END  **********************

		// ********************** 拓展库 START **********************
		$sql[] = "CREATE TABLE `test`
		(
		id int NOT NULL AUTO_INCREMENT,
		PRIMARY KEY(id),
		title varchar(50) COMMENT '标题'
		)ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
		";
		// **********************  拓展库 END  **********************

		$num = 0;
		foreach ($sql as $key=>$val) {
		    echo str("{0}.",[$key+1]);
		    $str = explode("(", $val);
		    $str = $str[0];
						try{
		    if ($db->query($val)) {
		        echo str("{0} [成功]",[$str]);
		    } else {
										throw new \mysqli_sql_exception;
		    }
						}
						catch(\Exception $exc){
								echo str("{0} [失败: {1}]",[$str,$db->error]);
						}
		    echo "<br />".PHP_EOL;
		}
		echo "######################################  创建表 END  ######################################";
		echo "<br />".PHP_EOL;


		echo "###################################### 添加初始数据 START ######################################";
		echo "<br />".PHP_EOL;

		//添加登陆账号
		//echo $db->query("SELECT * FROM `df`")->fetch_array()[1];   //读取首条数据
		$query = $db->query("SELECT COUNT(*) AS count FROM `df`")->fetch_array();
		if ($query[0] < 1) {
		    if ($db->query("insert into `df`(nm,pw,pic) values('df','df','/view/admin/public/assets/img/logo.png')")) {
		        echo "添加数据 [df] 成功";
		    } else {
		        echo "添加数据 [df] 失败";
		    }
		} else {
		    echo "数据 [df] 已存在";
		}
		echo "<br />".PHP_EOL;
		//添加账号权限
		$query = $db->query("SELECT COUNT(*) AS count FROM `roles`")->fetch_array();
		if ($query[0] < 1) {
		    if ($db->query("insert into `roles`(nm,roles) values('超级用户',''),('普通用户','1|2|7|10|15|16|')")) {
		        echo "添加数据 [roles] 成功";
		    } else {
		        echo "添加数据 [roles] 失败";
		    }
		} else {
		    echo "数据 [roles] 已存在";
		}
		echo "<br />".PHP_EOL;
		//添加默认布局
		$query = $db->query("SELECT COUNT(*) AS count FROM `home_layout`")->fetch_array();
		if ($query[0] < 1) {
		    if ($db->query("insert into `home_layout`(`title`,keywords,description,inscribe,img1,color) values('DfPHP','DfPHP,轻量级php框架','DfPHP——简洁的php框架','© 2023 Dfer.Site','/view/admin/public/assets/img/bg.jpg','#ffffff')")) {
		        echo "添加数据 [home_layout] 成功";
		    } else {
		        echo "添加数据 [home_layout] 失败";
		    }
		} else {
		    echo "数据 [home_layout] 已存在";
		}
		echo "<br />".PHP_EOL;

		//添加默认栏目
		$query = $db->query("SELECT COUNT(*) AS count FROM `home_column`")->fetch_array();
		if ($query[0] < 1) {
		    if ($db->query(
<<<STR
INSERT INTO `home_column` (`id`, `menu`, `title`, `describe`, `content`) VALUES
(1, "", "关键字说明", "", "<p>									</p><p>									</p><p>									</p><p><span style=\"white-space: nowrap;\">//布局</span></p><p><span style=\"white-space: nowrap;\">&nbsp;#header()</span></p><p><span style=\"white-space: nowrap;\">&nbsp;#body()</span></p><p><span style=\"white-space: nowrap;\">&nbsp;#footer()&nbsp;</span></p><p><span style=\"white-space: nowrap;\">&nbsp;#header{}#</span></p><p><span style=\"white-space: nowrap;\">&nbsp;#body{}#</span></p><p><span style=\"white-space: nowrap;\">&nbsp;#footer{}#</span></p><p><span style=\"white-space: nowrap;\">&nbsp;</span></p><p><span style=\"white-space: nowrap;\">&nbsp;//打印参数</span></p><p><span style=\"white-space: nowrap;\">&nbsp;!!$str!!</span></p><p><span style=\"white-space: nowrap;\">&nbsp;</span></p><p><span style=\"white-space: nowrap;\">&nbsp;//执行php代码</span></p><p><span style=\"white-space: nowrap;\">&nbsp;!{}!</span></p><p><span style=\"white-space: nowrap;\">&nbsp;</span></p><p><span style=\"white-space: nowrap;\">&nbsp;//遍历数组，来循环显示多条数据</span></p><p><span style=\"white-space: nowrap;\">&nbsp;!{each $arr}</span></p><p><span style=\"white-space: nowrap;\">&nbsp;!``</span></p><p><span style=\"white-space: nowrap;\">&nbsp;!{/each}</span></p><p><span style=\"white-space: nowrap;\">&nbsp;</span></p><p><span style=\"white-space: nowrap;\">//这里放关键字，防止整理代码格式的时候关键字被破坏</span></p><p><span style=\"white-space: nowrap;\">/*d<span style=\"white-space:pre\">	</span></span></p><p><span style=\"white-space: nowrap;\">d*/</span></p><p><span style=\"white-space: nowrap;\">&nbsp;</span></p><p><span style=\"white-space: nowrap;\">&nbsp;//if语句</span></p><p><span style=\"white-space: nowrap;\">&nbsp;!{if true}</span></p><p><span style=\"white-space: nowrap;\">&nbsp;!{elif false}</span></p><p><span style=\"white-space: nowrap;\">&nbsp;!{else}</span></p><p><span style=\"white-space: nowrap;\">&nbsp;!{/else}</span></p><p><span style=\"white-space: nowrap;\">&nbsp;</span></p><p><br /></p><p>								</p><p>								</p><p>								</p>"),
(2, "", "数据库操作", "", "<p><span style=\"white-space: nowrap;\">#查询#</span></p><p><span style=\"white-space: nowrap;\">//有多行就输出数组，否则返回单个list（有些情况必须返回数组，就添加order）</span></p><p><span style=\"white-space: nowrap;\">show(\"df\",1,\"type\",\" \");&nbsp; &nbsp;&nbsp;</span></p><p><span style=\"white-space: nowrap;\">// 根据字符串进行查询</span></p><p><span style=\"white-space: nowrap;\">show(\"df\",\"谷雨光影\",\"subs\");&nbsp;</span></p><p><span style=\"white-space: nowrap;\">// 按id降序输出全表&nbsp;&nbsp;</span></p><p><span style=\"white-space: nowrap;\">show(\"df\",-1,\"id\",\"desc\");<span style=\"white-space:pre\">	</span></span></p><p><span style=\"white-space: nowrap;\">//输出type为1的特定数目的数据</span></p><p><span style=\"white-space: nowrap;\">show(\"df\",1,\"type\",\"limit 0,5\");<span style=\"white-space:pre\">	</span></span></p><p><span style=\"white-space: nowrap;\">//输出type为1的数据并进行排序</span></p><p><span style=\"white-space: nowrap;\">show(\"df\",1,\"type\",\"order by id desc\");</span></p><p><span style=\"white-space: nowrap;\">//执行sql语句<span style=\"white-space:pre\">	</span></span></p><p><span style=\"white-space: nowrap;\">show(\"select * from df\",0);<span style=\"white-space:pre\">	</span></span></p><p><span style=\"white-space: nowrap;\">//按条件输出全表</span></p><p><span style=\"white-space: nowrap;\">show(\"menu\",$param,\"parent\",\"order by oderNum desc\");</span></p><p><span style=\"white-space: nowrap;\">//分页查询(页数,行数)</span></p><p><span style=\"white-space: nowrap;\">show_page(self::$db_d,$page,$rows);</span></p><p><span style=\"white-space: nowrap;\"><br /></span></p><p><span style=\"white-space: nowrap;\">##新增、修改##</span></p><p><span style=\"white-space: nowrap;\">//新增数据，之后不进行任何操作</span></p><p><span style=\"white-space: nowrap;\">update(\"df\",$arr)<span style=\"white-space:pre\">		</span></span></p><p><span style=\"white-space: nowrap;\">//根据id新增、修改数据，之后进行页面跳转</span></p><p><span style=\"white-space: nowrap;\">update(self::$db_hc,$dt,$id,(\"homepage/column/\".self::$db_hc));<span style=\"white-space:pre\">	</span></span></p><p><span style=\"white-space: nowrap;\"><br /></span></p><p><span style=\"white-space: nowrap;\"><br /></span></p><p><span style=\"white-space: nowrap;\">##删除##</span></p><p><span style=\"white-space: nowrap;\">//根据id进行删除</span></p><p><span style=\"white-space: nowrap;\">del(\"db\",3);</span></p><p><span style=\"white-space: nowrap;\">//清空表</span></p><p><span style=\"white-space: nowrap;\">clear(\"db\")</span></p><p><br /></p>"),
(3, "", "数据库操作返回json", "", "<p><span style=\"white-space: nowrap;\"><br /></span></p><p><span style=\"white-space: nowrap;\">#查询返回json数据</span></p><p><span style=\"white-space: nowrap;\">//根据id查询</span></p><p><span style=\"white-space: nowrap;\">tableToJson(\"df\",\"id\",\"desc\",1);</span></p><p><span style=\"white-space: nowrap;\">//根据time降序排列&nbsp;</span></p><p><span style=\"white-space: nowrap;\">tableToJson(\"df\",\"time\");&nbsp;</span></p><p><span style=\"white-space: nowrap;\">//根据time升序排列</span></p><p><span style=\"white-space: nowrap;\">tableToJson(\"df\",\"time\",\"asc\");</span></p><p><span style=\"white-space: nowrap;\">//自定义sql查询</span></p><p><span style=\"white-space: nowrap;\">tableToJson(\"sql\",\"select * from df\");</span></p><p><span style=\"white-space: nowrap;\">#更新返回json</span></p><p><span style=\"white-space: nowrap;\">jsonUpdate(\"db\",array(\"nm\"=&gt;\"123\"),3);</span></p><p><span style=\"white-space: nowrap;\">#清空</span></p><p><span style=\"white-space: nowrap;\">jsonClear(\"db\")</span></p><p><br /></p>"),
(4, "", "框架介绍", "", "<p>									</p><ul><li><p>- 由Df打造的php版的Mvc框架，结构简洁，使用方便</p></li><li><p>- 可以在此框架的基础上开发出各种各样的网站</p></li><li><p>- 有很好的拓展性，可以不断增加新的功能</p></li><li><p>- 由df提供技术支持</p></li><li><p>- 此项目将不断完善</p></li><li><p>- 工作QQ：3504725309&nbsp; &nbsp; &nbsp;&nbsp;</p></li><li><p>- 个人网站：www.dfer.site</p></li><li><p>- 论坛：www.szswz.cc&nbsp;</p></li><li><p>- QQ群：76673820</p></li></ul><p>&nbsp;</p><p><br /></p><p>								</p>");
STR)) {
		        echo "添加数据 [home_column] 成功";
		    } else {
		        echo "添加数据 [home_column] 失败";
		    }
		} else {
		    echo "数据 [home_column] 已存在";
		}
		echo "<br />".PHP_EOL;
		//添加通用参数
		$query = $db->query("SELECT COUNT(*) AS count FROM `dt`")->fetch_array();
		if ($query[0] < 1) {
		    if ($db->query("insert into `dt`(`key`,val,subs) values('hits','0','用户访问量'),('admin','0','开启超级权限')")) {
		        echo "添加数据 [dt] 成功";
		    } else {
		        echo "添加数据 [dt] 失败";
		    }
		} else {
		    echo "数据 [dt] 已存在";
		}
		echo "<br />".PHP_EOL;
		//添加静态页面
		$query = $db->query("SELECT COUNT(*) AS count FROM `html`")->fetch_array();
		if ($query[0] < 1) {
		    if ($db->query("insert into `html` (`file_n`,`src`) values('index','homepage/home/')")) {
		        echo "添加数据 [html] 成功";
		    } else {
		        echo "添加数据 [html] 失败";
		    }
		} else {
		    echo "数据 [html] 已存在";
		}
		echo "<br />".PHP_EOL;

		//添加基础菜单
		$query = $db->query("SELECT COUNT(*) AS count FROM `menu`")->fetch_array();
		if ($query[0] < 1) {
		    if ($db->query("INSERT INTO `menu` (`title`, `src`, `type`, `parent`, `order_num`) VALUES
		('动态首页', 'homepage%2Fhome%2F', 'home', 0, 0),
		('主页管理', '', 'folder', 0, 1),
		('用户管理', '', 'user', 0, 2),
		('生成静态页面', '', 'folder', 0, 8888),

		('记事本', 'admin%2Fcolumn%2Fnotepad', 'book', 0, 100),
		('记事本 服务器端处理', 'admin%2Fcolumn%2Fnotepad_ss', 'book', 0, 101),
		('关于此站点', 'admin%2Fcolumn%2Fcolumn	', 'info', 0, 110),
		('刷新数据', 'js%3Arefresh_data%28%29%3B', 'refresh', 0, 120),
		('装载数据', 'admin%2Fhome%2Fcreate_db', 'save', 0, 130),
		('框架信息', 'admin%2Fhome%2Finfo', 'info', 0, 140),
		('菜单', 'admin%2Fhome%2Fmenu', 'lock', 0, 150),
		('日志', 'admin%2Fhome%2Flog', 'history', 0, 160),
		('使用说明', 'admin%2Fcolumn%2Freadme', 'bug', 0, 170),

		('布局', 'admin%2Fcolumn%2Fhome_layout%2F1', 'file', 2, 0),
		('栏目管理', 'admin%2Fcolumn%2Fhome_column', 'file', 2, 0),
		('链接管理', 'admin%2Fcolumn%2Fhome_link', 'link', 2, 0),
		('音乐管理', 'admin%2Fcolumn%2Fhome_music', 'music', 2, 0),
		('留言管理', 'admin%2Fcolumn%2Fmessage', 'comments', 2, 0),

		('列表', 'admin%2Fhome%2Fdf', 'file', 3, 0),
		('权限', 'admin%2Fhome%2Froles', 'file', 3, 0),
		('访问者信息', 'admin%2Fhome%2Fguests', 'file', 3, 0),

		('查看字体', 'url%3A%2Fstatic_pages%2Ffont.html', 'file', 4, 8880),
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
		echo "<br />".PHP_EOL;

		echo "######################################  添加初始数据 END  ######################################";
		echo "<br />".PHP_EOL;



		echo "###################################### 更新数据库 START ######################################";
		echo "<br />".PHP_EOL;
		$sql_update = "";
		$dbPath=ROOT.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'db'.DIRECTORY_SEPARATOR;
		if (is_dir($dbPath)) {
		    $files = glob($dbPath . '*.sql');
		    foreach ($files as $file) {
										$sql_update=$sql_update.PHP_EOL.file_get_contents($file);
		    }
		}
		echo "<br />".PHP_EOL;
		if (!empty($sql_update)) {
			foreach(explode(';',$sql_update) as $key=>$value){
				if(empty(trim($value)))
						continue;
				try{
				   if ($db->multi_query($value)) {
				       echo str("<pre>{0} [更新成功]</pre>",[$value]);
				   } else {
											throw new \mysqli_sql_exception;
				   }
				}
				catch(\Exception $exc){
				       echo str("<pre>{0} [更新失败: {1}]</pre>",[$value,$db->error]);
				}
				echo "<br />".PHP_EOL;
			}
		} else {
		    echo "不需要更新";
		}
		echo "<br />".PHP_EOL;

		echo "######################################  更新数据库 END  ######################################";
		echo "<br />".PHP_EOL;

		$db->close();

		return true;
	}
}
