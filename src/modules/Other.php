<?php
defined('INIT') or exit('Access Denied');

class Other
{
    public function __construct()
    {
    }

    //数据集
    public $data = array('ses' => 'df-ac-pw', 'qq' => 'login');
    //获取数组内容
    public function getData($key)
    {
        return $this->data[$key];
    }

    /**
     *
     * 验证登陆
     * ses里保存了用户的id、nm、pw
     * @param {Object} $type	0 跳转 1 获取id
     */
    public function verifyLogin($type = 0)
    {
        global $common;

        $login = getSession($this->data["ses"]);

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
     * $this->log("{$url}<br>");
     * $this->log(json_encode($this->message)."<br>",7);    //写入数组
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
        if (isset($_GET['WxId'])) {
            return $_GET['WxId'];
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
            $dt = array('browser' => $_SERVER['HTTP_USER_AGENT'], 'hits' => $user['hits'] + 1, 'time' => $_df['time']);
            update($db, $dt, $user['Id']);
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
}
