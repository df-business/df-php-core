<?php

namespace Dfer\DfPhpCore\Modules;

use Dfer\Tools\{Common};

/**
 * +----------------------------------------------------------------------
 * | mysql数据库驱动
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
class Mysql extends Common
{

	/**
	 * 当前数据表名称（不含前缀）
	 * @var string
	 */
	protected $name;
	protected $json;
	protected $jsonAssoc;

	protected $field = array();
	protected $where = array();
	protected $order = array();
	protected $limit = array();


	public function getName(): string
	{
		return $this->name;
	}

	public function setup(array $item)
	{
		$this->name = $item['name'] ?? '';
		$this->json = $item['json'] ?? [];
		$this->jsonAssoc = $item['jsonAssoc'] ?? false;
		return $this;
	}

	/**
	 * 字段
	 */
	public function field($param = array())
	{
		$this->field = $param;
		return $this;
	}


	/**
	 * 条件
	 */
	public function where($param = array())
	{
		$this->where = $param;
		return $this;
	}

	/**
	 * 排序
	 */
	public function order($param = array())
	{
		$this->order = $param;
		return $this;
	}

	/**
	 * 限制
	 */
	public function limit($param = array())
	{
		$this->limit = $param;
		return $this;
	}

	/**
	 * 读取第一条数据
	 */
	public function first($field = null)
	{
		$r = $this->query($this->queryFormat());
		$rt = $r->fetch_array(MYSQLI_BOTH);
		return $rt;
	}

	/**
	 * 读取第一条数据,不满足条件则返回空
	 */
	public function find($field = null)
	{
		if (empty($this->where)) {
			$rt = [];
		} else {
			$r = $this->query($this->queryFormat());
			$rt = $r->fetch_array(MYSQLI_BOTH);

			foreach ($rt as $key => &$value) {
				if ($this->jsonAssoc && in_array($key, (array) $this->json)) {
					$value = json_decode($value, true);
				}
			}
		}
		return $rt;
	}

	/**
	 * 读取第一条数据的某个值
	 */
	public function value($field)
	{
		return $this->find()[$field];
	}

	/**
	 * 输出列表
	 *
	 * 返回数组
	 */
	public function select()
	{
		$r = $this->query($this->queryFormat());
		//始终返回数组
		$rt = $r->fetch_all(MYSQLI_BOTH);
		$rt = empty($rt) ? array() : $rt;
		return $rt;
	}



	/**
	 * 多行则返回数组
	 * 单行则返回键值对
	 */
	public function show()
	{
		$sql = $this->queryFormat();
		$r = $this->query($sql);

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


	/**
	 * 编辑
	 * $ret = LinkModel::where(3)->update($dt);
		* $ret = ConfigModel::where(['key' => 'layout'])->update(['val'=>$dt]);
		*
		* $ret = LinkModel::update($dt);
		* $ret = LinkModel::where(null)->update($dt);
		* $ret = LinkModel::where([])->update($dt);
		*
	 */
	public function update($data = array(), $redirect = null)
	{
		$sql = $this->queryFormatUpdateInsert($data);
		// var_dump($sql);
		$return = 0;
		// 新增
		if (empty($this->where)) {
			$return = $this->insert($data, $redirect);
		}
		// 编辑
		else {
			// 开启事务。防止高并发
			$this->query("START TRANSACTION");
			$r = $this->query($sql);
			// 提交事务
			$this->query("COMMIT");
			if ($r) {
				$return = 1;
			}
		}
		return $return;
	}

	/**
	 * 新增
	 *
	 * 获取新行id
	 * $ret = ConfigModel::insert(['val'=>123]);
		*
	 */
	public function insert($data = array(), $redirect = null)
	{
		$sql = $this->queryFormatUpdateInsert($data);
		//开启事务。防止高并发
		$this->query("START TRANSACTION");
		$r = $this->query($sql);
		//提交事务
		$this->query("COMMIT");
		$return = 0;
		if ($r) {
			$return = $this->run('SELECT LAST_INSERT_ID()');
			$return = $return[0][0]; //返回新增的id
		}
		return $return;
	}

	/**
	 * 删除数据
	 *
		* 根据id删除
		* ArticleModel::where($id)->del();
		*
	 * 根据条件删除
		* ArticleModel::where(['type'=>3])->del();
	 *
	 * 清空表
	 * ArticleModel::del()
	 *
	 */
	public function del($redirect = null)
	{
		global $db;
		$return = 0;

		$sql = $this->queryFormatDel();
		$r = $this->query($sql);

		if ($r) {
			$return = 1;
		}
		return $return;
	}

	/**
	 * dataTable依赖
	 *
	 * 分页处理
	 *
	 *
	 * showPage($db_Statistics,[],"Df_web_mng/data/".$db_Statistics);
	 */
	public function showPage($url = '')
	{
		if ($_POST) {
			$table_name = $this->name;

			$search = $_POST['search']['value'];
			$order_column = $_POST['order'][0]['column'];
			$order_column = $_POST['columns'][$order_column]['data'];
			$order_type = $_POST['order'][0]['dir'];
			$order = [$order_column, $order_type];

			$start = $_POST['start'];
			$length = $_POST['length'];
			$limit = [$start, $length];
			//var_dump($order_column,$_POST['order'][0]['column'],$_POST['columns']);

			$total_count = $this->run(sprintf("select count(*) from %s", $table_name))[0][0];
			// $data = showList($tb, $para, $order, [$start, $length]);
			$data = $this->order($order)->limit($limit)->select();
			$data_rt = array();
			if (!empty($url)) {
				foreach ($data as $key => $value) {
					$url_view = split_url(sprintf("%s_view/%s", $url, $value[0]));
					$url_edit = split_url(sprintf("%s_add/%s", $url, $value[0]));
					$url_del = split_url(sprintf("%s_del/%s", $url, $value[0]));
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

			$this->showJsonBase($return);
		}
	}


	/**
	 * 将表中的所有字段初始化为空字符串
	 * 数据不存在的时候用来填充数据
	 * @param {Object} $table
	 */
	public function tableInit()
	{
		$database = DATABASE;
		$table_name = $this->name;
		//获取表字段名、类型、备注
		$r = $this->run(sprintf("select column_name,data_type,column_comment from information_schema.COLUMNS where table_name = '%s' and table_schema = '%s'", $table_name, $database));
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



	/**
	 * 判断表是否存在
	 * @param {Object} $table
	 */
	public function tableExist($table = 'cache')
	{
		$row = $this->run("SHOW TABLES LIKE '" . $table . "'");
		if (!count($row)) {
			return false;
		}
		return true;
	}

	/**
	 * 查询字符串格式化
	 *
	 * 多列
	 * queryFormat('df',['type'=>1,'parent_id'=>2],['time','desc'],[0,1]); *
	 * queryFormat('df',['type'=>1],['time','desc'],10);
	 * queryFormat('df',['type'=>1],['time','desc']);
	 * queryFormat('df',['type'=>1]);
	 *
	 * 单列
	 * 默认param为id
	 * queryFormat('df',1);
	 * queryFormat('df',['type'=>1]);
	 */
	public function queryFormat()
	{
		$table_name = $this->name;
		$field = $this->field;
		$where = $this->where;
		$order = $this->order;
		$limit = $this->limit;

		if (empty($table_name)) {
			return null;
		}


		//拼接field
		if (empty($field)) {
			$field_string = '*';
		} elseif (is_string($field)) {
			$field_string = $field;
		} elseif (is_array($field)) {
			foreach ($field as $key => &$value) {
				if ($value !== null) {
					$value = "`{$value}`";
				}
			}
			$field_string = implode(",", $field);
		} else {
			$field_string = '*';
		}

		//拼接where
		if (empty($where)) {
			$where_string = '';
		} elseif (is_numeric($where)) {
			$where_string = 'where id=' . $where;
		} elseif (is_string($where)) {
			$where_string = 'where ' . $where;
		} elseif (is_array($where)) {
			$where_string = 'where 1=1';
			if (!empty($where)) {
				foreach ($where as $key => $value) {
					if ($value === null) {
						$where_string .= sprintf(" and `%s` is null", $key);
					} else {
						$where_string .= sprintf(" and `%s`='%s'", $key, $value);
					}
				}
			}
		}

		//拼接order
		if (empty($order)) {
			$order_string = '';
		} elseif (is_string($order)) {
			$order_string = 'order by id ' . $order;
		} elseif (is_array($order)) {
			if (count($order) == 2) {
				$order_string = sprintf('order by %s %s', $order[0], $order[1]);
			} else {
				$order_string = sprintf('order by %s %s', array_key_first($order), $order[array_key_first($order)]);
			}
		}

		//拼接limit
		if (empty($limit)) {
			$limit_string = '';
		} else {
			if (is_array($limit)) {
				$limit_string = sprintf('limit %s,%s', $limit[0], $limit[1]);
			} elseif (is_numeric($limit)) {
				$limit_string = sprintf('limit %s', $limit);
			}
		}

		//带条件获取整个表的数据
		$sqlString = sprintf("select %s from `%s` %s %s %s", $field_string, $table_name, $where_string, $order_string, $limit_string); //sql语句的表名区分大小写
		return $sqlString;
	}

	/**
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
	 */
	public function queryFormatUpdateInsert($data = array())
	{
		global $db;

		$table_name = $this->name;
		$where = $this->where;

		//新增
		if (empty($where)) {
			$data_str = $data_str_key = $data_str_val = '';
			if (!empty($data)) {
				foreach ($data as $key => $value) {
					if (empty($value)) {
						$value = $this->getTypeValue($table_name, $key);
					} elseif (is_int($value)) {
						$value = intval($value);
					} elseif (in_array($key, (array) $this->json)) {
						$value = json_encode($value, JSON_UNESCAPED_UNICODE);
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



			$sqlString = sprintf("insert into `%s` %s", $table_name, $data_str); //sql语句的表名区分大小写
		}
		//编辑
		else {
			$data_str = 'set';
			if (!empty($data)) {
				foreach ($data as $key => $value) {
					if (empty($value)) {
						$value = $this->getTypeValue($table_name, $key);
					} elseif (is_int($value)) {
						$value = intval($value);
					} elseif (in_array($key, (array) $this->json)) {
						$value = json_encode($value, JSON_UNESCAPED_UNICODE);
					} else {
						$value = mysqli_escape_string($db, $value);
					}
					$data_str .= sprintf(" `%s`='%s',", $key, $value);
				}
			}
			//去掉尾部逗号
			$data_str = substr($data_str, 0, -1);


			//拼接where
			if (is_numeric($where)) {
				$where_string = 'where id=' . $where;
			} elseif (is_string($where)) {
				$where_string = 'where ' . $where;
			} else {
				$where_string = 'where 1=1';
				if (!empty($where)) {
					foreach ($where as $key => $value) {
						$where_string .= sprintf(" and `%s`='%s'", $key, $value);
					}
				}
			}


			$sqlString = sprintf("update `%s` %s %s", $table_name, $data_str, $where_string); //sql语句的表名区分大小写
		}
		//var_dump($sqlString);die();
		return $sqlString;
	}

	/**
	 * 删除数据
	 *
	 * queryFormatDel('df',['type'=>3])
	 *
	 * 根据id删除
	 * queryFormatDel('df',5)
	 *
	 * 清空表
	 * queryFormatDel('df')
	 */
	public function queryFormatDel()
	{
		$table_name = $this->name;
		$where = $this->where;

		// 拼接where
		if (is_numeric($where)) {
			$where_string = 'where id=' . $where;
		} elseif (is_string($where)) {
			$where_string = 'where ' . $where;
		} else {
			$where_string = 'where 1=1';
			if (!empty($where)) {
				foreach ($where as $key => $value) {
					$where_string .= sprintf(" and `%s`='%s'", $key, $value);
				}
			}
		}

		// sql语句的表名区分大小写
		$sqlString = sprintf("delete from `%s` %s", $table_name, $where_string);

		return $sqlString;
	}



	/**
	 * 根据字段类型获取默认值
	 * @param {Object} $tb
	 * @param {Object} $column
	 */
	public function getTypeValue($tb, $column)
	{
		$sql = sprintf("SELECT
				 NUMERIC_SCALE,COLUMN_NAME,DATA_TYPE
				FROM
				    information_schema. COLUMNS
				WHERE TABLE_NAME = '%s' and COLUMN_NAME='%s';
				", $tb, $column);
		$dt = $this->run($sql);
		$value = $dt[0][0];
		return $value;
	}



	/**
	 * 简洁执行sql语句
	 */
	public function run($sql)
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
	public function query($sql)
	{
		global $db;
		debug($sql);
		$r = $db->query($sql);
		//容错处理
		if (!empty($db->error)) {
			$err = sprintf("语句：%s %s 错误信息：%s", $sql, PHP_EOL, json_encode($db->error));
			echo $err;
			debug($err);
		}
		return $r;
	}

	/**
	 * 连接sql服务器，执行sql语句
	 * 单刀插入数据（无视一切规则，强行添加）
	 * 支持远程连接
	 * @param {Object} $tb
	 * @param {Object} $data
	 */
	public function add($tb, $data)
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
		$r = query($sql);
		return $r;
	}

	/*开始事务
	 *
	 *停用自动提交
	 *检测表是否支持事务
	 */
	public function begin($table = array())
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
	public function commit()
	{
		global $db;
		$db->commit();
		//恢复自动提交
		$db->autocommit(true);
	}

	//回滚
	public function back()
	{
		global $db;
		$db->rollback();
	}

	//关闭连接
	public function close()
	{
		global $db;
		$db->close();
	}

	/*
	 *根据sql的返回值调用事务
	 *
	 *执行sql失败就回滚
	 */
	public function affair($v)
	{
		if (!$v) {
			$this->back();
		}
	}


	/**
	 * 数据库连接初始化
	 * @param {Object} $var 变量
	 **/
	public function init()
	{
		$con = mysqli_connect(SERVER, ACC, PWD);
		if (!$con) {
			echo "服务器 [" . SERVER . "] 连接失败";
			echo "<br>";
			die();
		}
		$database = DATABASE;
		try {

			// ********************** 连接数据库 START **********************

			if (mysqli_select_db($con, $database)) {
				//连接数据库，忽略错误
				@$db = new \MySQLi(SERVER, ACC, PWD, $database);
				!mysqli_connect_error() or die("数据库 [{$database}] 错误");
				//防止乱码
				$db->query('set names utf8');
				// 判断数据库是否为空
				$query = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '{$database}';")->fetch_array();
				if ($query[0] == 0) {
					if ($this->create($db)) {
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
				}
			} else {
				throw new \mysqli_sql_exception;
			}
			// **********************  连接数据库 END  **********************
		} catch (\Exception $exc) {
			// ********************** 创建数据库 START **********************

			if (mysqli_query($con, "CREATE DATABASE {$database}")) {
				echo str("数据库 {0} 创建成功 <br /> {1}", [$database, PHP_EOL]);
				@$db = new \MySQLi(SERVER, ACC, PWD, $database);
				!mysqli_connect_error() or die("数据库 [{$database}] 错误");
				$db->query('set names utf8');
				if ($this->create($db)) {
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
			// **********************  创建数据库 END  **********************
		}

		return $db;
	}




	/**
	 * 创建表
	 * @param {Object} $con 数据库连接对象
	 * @param {Object} $database 数据库名称
	 **/
	public function create($db)
	{

		echo "###################################### 创建表 START ######################################";
		echo "<br />" . PHP_EOL;


		// ********************** 核心库 START **********************
		$sql[] = "CREATE TABLE `user` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `nm` varchar(50) CHARACTER SET utf8 DEFAULT 'df' COMMENT '账号名',
			  `pw` varchar(50) CHARACTER SET utf8 DEFAULT 'df',
			  `pic` varchar(200) CHARACTER SET utf8 DEFAULT '/favicon.png',
			  `role` int(11) NOT NULL DEFAULT '0' COMMENT '权限',
			  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
			  `last_login_time` datetime DEFAULT NULL COMMENT '上次访问',
			  PRIMARY KEY (`id`) USING BTREE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='后台登陆账号，不要删';
					";
		$sql[] = "CREATE TABLE `roles` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `nm` varchar(50) CHARACTER SET utf8 DEFAULT '普通用户' COMMENT '权限名',
			  `roles` varchar(100) CHARACTER SET utf8 DEFAULT '1|2' COMMENT '权限内容',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='账号权限，不要删';
					";

		$sql[] = "CREATE TABLE `config` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `key` varchar(15) CHARACTER SET utf8 DEFAULT '' COMMENT '参数名',
			  `val` longtext CHARACTER SET utf8 COMMENT '值',
			  `subs` varchar(100) CHARACTER SET utf8 DEFAULT '' COMMENT '描述',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='基础参数，不要删';
					";

		$sql[] = "CREATE TABLE `menu` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `title` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT '标题',
			  `src` varchar(100) CHARACTER SET utf8 DEFAULT NULL COMMENT '路径',
			  `type` varchar(30) CHARACTER SET utf8 DEFAULT NULL COMMENT '类型',
			  `parent` int(11) DEFAULT '0' COMMENT '上级id',
			  `order_num` int(11) DEFAULT '0' COMMENT '排序编号',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台菜单，不要删';
					";

		$sql[] = "CREATE TABLE `html` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `file_n` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT 'htm文件名',
			  `src` varchar(100) CHARACTER SET utf8 DEFAULT NULL COMMENT '动态路径',
			  `comment` varchar(30) CHARACTER SET utf8 DEFAULT NULL COMMENT '备注',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='html页面，不要删';
					";
		$sql[] = "CREATE TABLE `cache` (
			  `key` varchar(50) CHARACTER SET utf8 NOT NULL,
			  `value` longtext CHARACTER SET utf8 NOT NULL,
			  PRIMARY KEY (`key`) USING BTREE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='服务器缓存,不要删';
			";

		$sql[] = "CREATE TABLE `logs` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `str` longtext CHARACTER SET utf8 COMMENT '记录内容',
			  `time` datetime DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='日志，不要删';
					";
		// **********************  核心库 END  **********************

		// ********************** 基础库 START **********************
		$sql[] = "CREATE TABLE `user_info` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `ip` varchar(55) CHARACTER SET utf8 DEFAULT '' COMMENT '访问者ip',
			  `browser` varchar(500) CHARACTER SET utf8 DEFAULT '' COMMENT '访问者使用的浏览器',
			  `hits` int(11) DEFAULT '0' COMMENT '访问总次数',
			  `first_time` datetime DEFAULT NULL COMMENT '访问者首次访问的时间',
			  `time` datetime DEFAULT NULL COMMENT '访问者最近访问的时间',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户信息收集';
			";

		$sql[] = "CREATE TABLE `layout_img` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `title` varchar(100) CHARACTER SET utf8 DEFAULT '',
			  `img` varchar(100) CHARACTER SET utf8 DEFAULT '' COMMENT '背景图像',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='背景图片列表';
			";

		$sql[] = "CREATE TABLE `article` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `title` varchar(100) CHARACTER SET utf8 DEFAULT '',
			  `describe` varchar(100) CHARACTER SET utf8 DEFAULT '',
			  `content` longtext CHARACTER SET utf8 COMMENT '内容',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章';
			";

		$sql[] = "CREATE TABLE `link` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `title` varchar(100) CHARACTER SET utf8 DEFAULT '',
			  `src` varchar(100) CHARACTER SET utf8 DEFAULT '',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='链接';
			";

		$sql[] = "CREATE TABLE `music` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `title` varchar(100) CHARACTER SET utf8 DEFAULT '',
			  `src` varchar(100) CHARACTER SET utf8 DEFAULT '',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='音乐';
			";

		$sql[] = "CREATE TABLE `message` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `name` varchar(100) CHARACTER SET utf8 DEFAULT '',
			  `e_mail` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
			  `content` longtext CHARACTER SET utf8 COMMENT '内容',
			  `status` tinyint(4) DEFAULT '0' COMMENT '阅读状态',
			  `time` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='留言';
			";

		$sql[] = "CREATE TABLE `notepad` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `title` varchar(50) CHARACTER SET utf8 DEFAULT NULL COMMENT '标题',
			  `content` longtext CHARACTER SET utf8 COMMENT '内容',
			  `time` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='记事本';
					";


		// **********************  基础库 END  **********************

		$num = 0;
		foreach ($sql as $key => $val) {
			echo str("{0}.", [$key + 1]);
			$str = explode("(", $val);
			$str = $str[0];
			try {
				if ($db->query($val)) {
					echo str("{0} [成功]", [$str]);
				} else {
					throw new \mysqli_sql_exception;
				}
			} catch (\Exception $exc) {
				echo str("{0} [失败: {1}]", [$str, $db->error]);
			}
			echo "<br />" . PHP_EOL;
		}
		echo "######################################  创建表 END  ######################################";
		echo "<br />" . PHP_EOL;
		echo "<br />" . PHP_EOL;
		echo "###################################### 添加数据 START ######################################";
		echo "<br />" . PHP_EOL;
		//添加登陆账号
		$query = $db->query("SELECT COUNT(*) AS `count` FROM `user`")->fetch_array();
		if ($query[0] < 1) {
			if ($db->query("insert into `user`(`nm`,`pw`,`pic`,`create_time`) values('df','df','/view/admin/public/assets/img/logo.png','2024-02-27 16:01:24')")) {
				echo "添加数据 [user] 成功";
			} else {
				echo "添加数据 [user] 失败";
			}
		} else {
			echo "数据 [user] 已存在";
		}
		echo "<br />" . PHP_EOL;
		//添加账号权限
		$query = $db->query("SELECT COUNT(*) AS count FROM `roles`")->fetch_array();
		if ($query[0] < 1) {
			if ($db->query("insert into `roles`(`nm`,`roles`) values('超级用户',''),('普通用户','1|2|7|10|15|16|')")) {
				echo "添加数据 [roles] 成功";
			} else {
				echo "添加数据 [roles] 失败";
			}
		} else {
			echo "数据 [roles] 已存在";
		}
		echo "<br />" . PHP_EOL;
		//添加默认文章
		$query = $db->query("SELECT COUNT(*) AS count FROM `article`")->fetch_array();
		if ($query[0] < 1) {
			if (
				$db->query(
					<<<STR
			INSERT INTO `article` (`title`, `describe`, `content`) VALUES
			("关键字说明", "", "<p><span style=\"text-wrap: nowrap;\">&lt;!-- 布局 --&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;df-html&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;/df-html&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;df-header&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;/df-header&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;df-body&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;/df-body&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;df-footer&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;/df-footer&gt;</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">&lt;df-html/&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;df-header/&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;df-body/&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;df-footer/&gt;</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">&lt;!-- 遍历数组，来循环显示多条数据 --&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;df-each $list&gt;</span></p><p>	<span style=\"text-wrap: nowrap;\">&lt;df-val-cache value=&quot;name&quot;/&gt;</span></p><p>	<span style=\"text-wrap: nowrap;\">{:::name}</span></p><p><span style=\"text-wrap: nowrap;\">&lt;/df-each&gt;</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">&lt;df-each-cache $list&gt;</span></p><p>	<span style=\"text-wrap: nowrap;\">&lt;df-val value=&quot;name&quot;/&gt;</span></p><p>	<span style=\"text-wrap: nowrap;\">{::name}</span></p><p><span style=\"text-wrap: nowrap;\">&lt;/df-each-cache&gt;</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">&lt;!-- 条件语句--&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;df-if $type==1&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;df-elif $type==2&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;df-else&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;/df-if&gt;</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">&lt;!-- 执行php代码 --&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;df-code&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;/df-code&gt;</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">&lt;!-- 打印参数 --&gt;</span></p><p><span style=\"text-wrap: nowrap;\">&lt;df-print value=&quot;$str&quot; /&gt;</span></p><p><span style=\"text-wrap: nowrap;\">{:$str}</span></p><p><span style=\"text-wrap: nowrap;\">{str}</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">&lt;!-- js防止格式化 --&gt;</span></p><p><span style=\"text-wrap: nowrap;\">/*code</span></p><p><span style=\"text-wrap: nowrap;\">code*/</span></p><p><br/></p>"),
			("数据库操作", "", "<p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">**数据库更新**</span></p><p><span style=\"text-wrap: nowrap;\">```</span></p><p><span style=\"text-wrap: nowrap;\">http://dfphp.dfer.site/admin/login/create_db</span></p><p><span style=\"text-wrap: nowrap;\">```</span></p><p><span style=\"text-wrap: nowrap;\">**引用模型**</span></p><p><span style=\"text-wrap: nowrap;\">```</span></p><p><span style=\"text-wrap: nowrap;\">use areas\\admin\\model\\{ConfigModel,LayoutImgModel,ArticleModel,LinkModel,MusicModel,MessageModel,NotepadModel};</span></p><p><span style=\"text-wrap: nowrap;\">```</span></p><p><span style=\"text-wrap: nowrap;\">**查询**</span></p><p><span style=\"text-wrap: nowrap;\">```</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">&lt;!-- 列表 --&gt;</span></p><p><span style=\"text-wrap: nowrap;\">$output = MusicModel::select();</span></p><p><span style=\"text-wrap: nowrap;\">$output = ArticleModel::order(&#39;asc&#39;)-&gt;select();</span></p><p><span style=\"text-wrap: nowrap;\">$output = NotepadModel::order([&#39;time&#39;, &#39;desc&#39;])-&gt;select();</span></p><p><span style=\"text-wrap: nowrap;\">$output = MusicModel::where(3)-&gt;select();</span></p><p><span style=\"text-wrap: nowrap;\">$output = MusicModel::where([&quot;id&quot; =&gt; 3])-&gt;select();</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">&lt;!-- 读取第一条数据,不满足条件则返回空 --&gt;</span></p><p><span style=\"text-wrap: nowrap;\">$output = ArticleModel::where(3)-&gt;find();</span></p><p><span style=\"text-wrap: nowrap;\">&lt;!-- 始终读取第一条数据 --&gt;</span></p><p><span style=\"text-wrap: nowrap;\">$output = NotepadModel::where([&quot;id&quot; =&gt; 3])-&gt;first();</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">&lt;!-- 直接生成dataTable的接口数据 --&gt;</span></p><p><span style=\"text-wrap: nowrap;\">NotepadModel::showPage(str(&quot;admin/column/{0}_ss&quot;,[NotepadModel::getName()]));</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">&lt;!-- 读取第一条数据的某个值 --&gt;</span></p><p><span style=\"text-wrap: nowrap;\">$layout = ConfigModel::where([&#39;key&#39; =&gt; &#39;layout&#39;])-&gt;value(&#39;val&#39;);</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">```</span></p><p><span style=\"text-wrap: nowrap;\">**新增**</span></p><p><span style=\"text-wrap: nowrap;\">```</span></p><p><span style=\"text-wrap: nowrap;\">$ret = ConfigModel::insert([&#39;val&#39;=&gt;123]);</span></p><p><span style=\"text-wrap: nowrap;\">$ret = LinkModel::update($dt);</span></p><p><span style=\"text-wrap: nowrap;\">$ret = LinkModel::where(null)-&gt;update($dt);</span></p><p><span style=\"text-wrap: nowrap;\">$ret = LinkModel::where([])-&gt;update($dt);</span></p><p><span style=\"text-wrap: nowrap;\">```</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">**修改**</span></p><p><span style=\"text-wrap: nowrap;\">```</span></p><p><span style=\"text-wrap: nowrap;\">$ret = LinkModel::where(3)-&gt;update($dt);</span></p><p><span style=\"text-wrap: nowrap;\">$ret = ConfigModel::where([&#39;key&#39; =&gt; &#39;layout&#39;])-&gt;update([&#39;val&#39;=&gt;$dt]);</span></p><p><span style=\"text-wrap: nowrap;\">```</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">**删除**</span></p><p><span style=\"text-wrap: nowrap;\">```</span></p><p><span style=\"text-wrap: nowrap;\">&lt;!-- 根据id删除 --&gt;</span></p><p><span style=\"text-wrap: nowrap;\">ArticleModel::where(3)-&gt;del();</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">&lt;!-- 根据条件删除 --&gt;</span></p><p><span style=\"text-wrap: nowrap;\">ArticleModel::where([&#39;type&#39;=&gt;3])-&gt;del();</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">&lt;!-- 清空表 --&gt;</span></p><p><span style=\"text-wrap: nowrap;\">ArticleModel::del();</span></p><p><span style=\"text-wrap: nowrap;\"><br/></span></p><p><span style=\"text-wrap: nowrap;\">```</span></p><p><br/></p>"),
			("框架介绍", "", "<p></p><ul><li><p>- 由Df打造的php版的Mvc框架，结构简洁，使用方便</p></li><li><p>- 可以在此框架的基础上开发出各种各样的网站</p></li><li><p>- 有很好的拓展性，可以不断增加新的功能</p></li><li><p>- 由df提供技术支持</p></li><li><p>- 此项目将不断完善</p></li><li><p>- 工作QQ：3504725309&nbsp; &nbsp; &nbsp;&nbsp;</p></li><li><p>- 个人网站：www.dfer.site</p></li><li><p>- 论坛：www.szswz.cc&nbsp;</p></li><li><p>- QQ群：76673820</p></li></ul><p>&nbsp;</p><p><br /></p><p></p>");
			STR
				)
			) {
				echo "添加数据 [article] 成功";
			} else {
				echo "添加数据 [article] 失败";
			}
		} else {
			echo "数据 [article] 已存在";
		}
		echo "<br />" . PHP_EOL;
		//添加通用参数
		$query = $db->query("SELECT COUNT(*) AS count FROM `config`")->fetch_array();
		if ($query[0] < 1) {
			if (
				$db->query(<<<STR
			insert into `config`(`key`,`val`,`subs`) values
			('hits','0','用户访问量'),
			('admin','0','开启超级权限'),
			('layout','{"bg_img":"/view/admin/public/assets/img/bg.jpg","music_play":"0","color":"#ffffff","title":"DfPHP","keywords":"DfPHP,轻量级php框架,化繁为简,返璞归真,大道至简","description":"遵循大道至简的php框架","inscribe":"© 2023 Dfer.Site"}','主页布局')
			STR)
			) {
				echo "添加数据 [config] 成功";
			} else {
				echo "添加数据 [config] 失败";
			}
		} else {
			echo "数据 [config] 已存在";
		}
		echo "<br />" . PHP_EOL;
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
		echo "<br />" . PHP_EOL;

		//添加基础菜单
		$query = $db->query("SELECT COUNT(*) AS count FROM `menu`")->fetch_array();
		if ($query[0] < 1) {
			if (
				$db->query("INSERT INTO `menu` (`title`, `src`, `type`, `parent`, `order_num`) VALUES
					('动态首页', 'homepage%2Fhome%2F', 'home', 0, 1),
					('内容管理', '', 'folder', 0, 2),
					('用户管理', '', 'user', 0, 3),
					('记事本', '', 'folder', 0, 4),
					('静态页面', '', 'folder', 0, 5),
					('刷新数据', 'js%3Arefresh_data%28%29%3B', 'refresh', 0, 6),
					('装载数据', 'admin%2Flogin%2Fcreate_db', 'save', 0, 7),
					('框架信息', 'admin%2Fhome%2Finfo', 'info', 0, 8),
					('菜单', 'admin%2Fhome%2Fmenu', 'lock', 0, 9),
					('日志', 'admin%2Fhome%2Flog', 'history', 0, 10),

					('布局', 'admin%2Fcolumn%2Flayout%2F1', 'file', 2, 0),
					('文章', 'admin%2Fcolumn%2Farticle', 'file', 2, 0),
					('链接', 'admin%2Fcolumn%2Flink', 'link', 2, 0),
					('音乐', 'admin%2Fcolumn%2Fmusic', 'music', 2, 0),
					('留言', 'admin%2Fcolumn%2Fmessage', 'comments', 2, 0),

					('列表', 'admin%2Fhome%2Fuser', 'file', 3, 0),
					('权限', 'admin%2Fhome%2Froles', 'file', 3, 0),
					('访问者信息', 'admin%2Fhome%2Fguests', 'file', 3, 0),

					('静态分页', 'admin%2Fcolumn%2Fnotepad', 'book', 4, 0),
					('动态分页', 'admin%2Fcolumn%2Fnotepad_ss', 'book', 4, 1),

					('生成', 'admin%2Fhome%2FcreateStaticPage', 'file', 5, 2),
					('页面', 'admin%2Fhome%2Fhtml', 'file', 5, 1),
					('字体', 'url%3A%2Fstatic_pages%2Ffont.html', 'file', 5, 0)
					;")
			) {
				echo "添加数据 [menu] 成功";
			} else {
				echo "添加数据 [menu] 失败";
			}
		} else {
			echo "数据 [menu] 已存在";
		}
		echo "<br />" . PHP_EOL;

		echo "######################################  添加数据 END  ######################################";
		echo "<br />" . PHP_EOL;
		echo "<br />" . PHP_EOL;
		echo "###################################### 更新 START ######################################";
		echo "<br />" . PHP_EOL;
		$sql_update = "";
		$dbPath = ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR;
		if (is_dir($dbPath)) {
			$files = glob($dbPath . '*.sql');
			foreach ($files as $file) {
				$sql_update = $sql_update . PHP_EOL . file_get_contents($file);
			}
		}
		echo "<br />" . PHP_EOL;
		if (!empty($sql_update)) {
			foreach (explode(';', $sql_update) as $key => $value) {
				if (empty(trim($value)))
					continue;
				try {
					if ($db->multi_query($value)) {
						echo str("<pre>{0} [更新成功]</pre>", [$value]);
					} else {
						throw new \mysqli_sql_exception;
					}
				} catch (\Exception $exc) {
					echo str("<pre>{0} [更新失败: {1}]</pre>", [$value, $db->error]);
				}
				echo "<br />" . PHP_EOL;
			}
		} else {
			echo "不需要更新";
		}
		echo "<br />" . PHP_EOL;

		echo "######################################  更新 END  ######################################";
		echo "<br />" . PHP_EOL;

		$db->close();

		return true;
	}
}
