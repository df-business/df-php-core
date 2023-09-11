<?php
/*
 * 存放常用函数的对象
 *
 * 函数集合
 * 放一些不常用的函数
 */
defined('INIT') or exit('Access Denied');

class Model
{
    public function __construct()//初始函数
    {
    }

    //数据集
    public $data = array('ses' => 'df-ac-pw', 'qq' => 'login');
    //获取数组内容
    public function getData($key)
    {
        return $this -> data[$key];
    }

    //------------------------------------------常用方法

    //截取指定两个字符之间的字符串
    public function str_cut($begin, $end, $str)
    {
        $b = mb_strpos($str, $begin) + mb_strlen($begin);
        $e = mb_strpos($str, $end) - $b;
        return mb_substr($str, $b, $e);
    }

    //php低版本无法兼容高版本，需要进行警告
    public function php_ver_notice()
    {
        $ver = explode(".", PHP_VERSION);
        $ver_0 = $ver[0];
        if ($ver_0 < DF_PHP_VER) {
            show_message("PHP版本不符合要求", sprintf("需要至少php%s", DF_PHP_VER), null, "error");
        }
    }

    //获取浏览器内核信息
    public function getBrowser()
    {
        return sprintf("%s-%s", $this -> getBrowser_name(), $this -> getBrowser_ver());
    }

    //ie兼容性差，对ie内核进行警告
    public function ie_notice()
    {
        //	echo $this->getBrowser_name();
        if ($this -> getBrowser_name() == 'ie') {
            show_message("不支持IE内核", "请检查浏览器");
        }
    }

    public function getBrowser_name()
    {
        $agent = $_SERVER["HTTP_USER_AGENT"];
        if (strpos($agent, 'MSIE') !== false || strpos($agent, 'rv:11.0')) {//ie11判断
            return "ie";
        } elseif (strpos($agent, 'Firefox') !== false) {//火狐
            return "firefox";
        } elseif (strpos($agent, 'Chrome') !== false) {//谷歌
            return "chrome";
        } elseif (strpos($agent, 'Opera') !== false) {//opera
            return 'opera';
        } elseif ((strpos($agent, 'Chrome') == false) && strpos($agent, 'Safari') !== false) {
            return 'safari';
        }
    }

    public function getBrowser_ver()
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {//当浏览器没有发送访问者的信息的时候
            return 'unknow';
        }
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/MSIE\s(\d+)\..*/i', $agent, $regs)) {//IE浏览器版本号
            return $regs[1];
        } elseif (preg_match('/FireFox\/(\d+)\..*/i', $agent, $regs)) {//火狐浏览器版本号
            return $regs[1];
        } elseif (preg_match('/Opera[\s|\/](\d+)\..*/i', $agent, $regs)) {//opera浏览器版本号
            return $regs[1];
        } elseif (preg_match('/Chrome\/(\d+)\..*/i', $agent, $regs)) {//谷歌浏览器版本号
            return $regs[1];
        } elseif ((strpos($agent, 'Chrome') == false) && preg_match('/Safari\/(\d+)\..*$/i', $agent, $regs)) {
            return $regs[1];
        } else {
            return 'unknow';
        }
    }

    /**
     unicode解码
     */
    public function unicode_decode($unistr)
    {
        // $unistr=substr($unistr,2,strlen($unistr));
        $json = '{"str":"' . $unistr . '"}';
        $arr = json_decode($json, true);
        if (empty($arr)) {
            return '';
        }
        return $arr['str'];
    }

    /*
     * 取得字符串中所有的图片地址
     *
     * $content		内容
     *
     * $order
     *All 所有图片
     *0 第一张图片
     */

    public function getImgs($content, $order = 'ALL')
    {
        // 	 	var_dump($content);
        $pattern = "/<img.*?src=[\'|\"](.*?(?:[\.gif|\.jpg\.png\.JPG]))[\'|\"].*?[\/]?>/";
        preg_match_all($pattern, $content, $match);

        if (isset($match[1]) && !empty($match[1])) {
            if ($order === 'ALL') {
                return $match[1];
            }
            if (is_numeric($order) && isset($match[1][$order])) {
                return $match[1][$order];
            }
        }
        return '';
    }

    /*
     * 取得页面所有的图片地址
     *
     * $content
     * 网址
     *
     * $order
     *All 所有图片
     *0 第一张图片
     */

    public function getImgsWeb($content, $order = 'ALL')
    {
        //获取网页内容
        $content = file_get_contents($content);
        // 	 	var_dump($content);
        $pattern = "/<img.*?src=[\'|\"](.*?(?:[\.gif|\.jpg\.png\.JPG]))[\'|\"].*?[\/]?>/";
        preg_match_all($pattern, $content, $match);

        if (isset($match[1]) && !empty($match[1])) {
            if ($order === 'ALL') {
                return $match[1];
            }
            if (is_numeric($order) && isset($match[1][$order])) {
                return $match[1][$order];
            }
        }
        return '';
    }

    /*创建图片对象
     * 只对有正常物理路径的图片有效
     * 对于缓存类图片无效
     */
    public function create_image($img)
    {
        $ext = strtolower(substr($img, strrpos($img, '.')));
        if ($ext == '.png') {
            $thumb = imagecreatefrompng($img);
        } elseif ($ext == '.gif') {
            $thumb = imagecreatefromgif($img);
        } else {
            $thumb = imagecreatefromjpeg($img);
        }
        return $thumb;
    }

    //遍历目录，获取目录、文件数组
    public $my_scenfiles = array();
    public $my_files = array();
    public function my_scandir($dir)
    {
        global $my_scenfiles, $my_files;
        //  echo $dir;
        if ($handle = opendir($dir)) {
            while (($file = readdir($handle)) !== false) {
                if ($file != ".." && $file != ".") {
                    if (is_dir($dir . "/" . $file)) {
                        my_scandir($dir . "/" . $file);
                    } else {
                        $my_scenfiles[] = $dir . "/" . $file;
                        $my_files[] = $file;
                    }
                }
            }
            closedir($handle);
        }
    }

    //拼装随机数，保留0位小数，生成一个字符串
    public function byte_format($input, $prec = 0)
    {
        $prefix_arr = array('D', 'F', 'E', 'R', 'R');
        $value = round($input, $prec);
        $i = 0;
        while ($value > 1024) {
            $value /= 1024;
            $i++;
        }
        $return_str = round($value, $prec) . $prefix_arr[$i];
        return $return_str;
    }

    //数组中所有元素都是数组则返回true
    public function is_array2($array)
    {
        if (is_array($array)) {
            foreach ($array as $k => $v) {
                return is_array($v);
            }
        }
        return false;
    }

    //获取最后一天的日期
    public function get_last_day($year, $month)
    {
        return date('t', strtotime("{$year}-{$month} -1"));
    }

    /*
     * 判断是否是时间戳
     */
    public function is_timestamp($timestamp)
    {
        if (strtotime(date('m-d-Y H:i:s', $timestamp)) === $timestamp) {
            return $timestamp;
        } else {
            return false;
        }
    }

    //base64加密
    public function b64_encode($obj)
    {
        if (is_array($obj)) {
            return urlencode(base64_encode(json_encode($obj)));
        }

        return urlencode(base64_encode($obj));
    }

    //base64解密
    public function b64_decode($str, $is_array = true)
    {
        $str = base64_decode(urldecode($str));

        if ($is_array) {
            return json_decode($str, true);
        }

        return $str;
    }

    /*读取文件的所有字符串
     *
     * 物理路径
     */
    public function ReadFileStr($fileN)
    {
        $file_path = $fileN;
        if (file_exists($file_path)) {
            $str = file_get_contents($file_path);
            //将整个文件内容读入到一个字符串中
            $str = str_replace("\n", "<br />", $str);
            return $str;
        } else {
            return 'file not exist';
        }
    }

    //将数组中的元素进行html原样输出
    public function ihtmlspecialchars($var)
    {
        if (is_array($var)) {
            foreach ($var as $key => $value) {
                $var[htmlspecialchars($key)] = $this -> ihtmlspecialchars($value);
            }
        } else {
            $var = str_replace('&amp;', '&', htmlspecialchars($var, ENT_QUOTES));
        }
        return $var;
    }

    //获取当前页面的完整网址
    public function getCurUrl($getPort = 0)
    {
        if (!$getPort) {
            return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            return 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        }
        //包含端口号的完整url
    }

    //执行控制台命令
    public function runShell($shell)
    {
        //	 echo exec('whoami');
        echo exec($shell);
    }

    //删除目录和目录下的文件，成功则返回1
    //$dir为目录的物理路径
    public function deldir($dir)
    {
        try {
            //先删除目录下的文件：
            $dh = opendir($dir);
            if ($dh==false) {
                return $dh;
            }
            while ($file = readdir($dh)) {
                if ($file != "." && $file != "..") {
                    $fullpath = $dir . "/" . $file;
                    if (!is_dir($fullpath)) {
                        $rt = unlink($fullpath);
                        if (!$rt) {
                            return false;
                        }
                    } else {
                        $this -> deldir($fullpath);
                        //循环删除文件
                    }
                }
            }
            closedir($dh);
            //删除当前文件夹
            return rmdir($dir);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    //删除单个文件
    public function delfile($file)
    {
        return unlink($file);
    }

    //遍历一个目录下的所有文件和文件夹，返回一个easyUI的json字符串
    public function getNextTree($dir)
    {
        $fileArr = '';
        $dirArr = '';
        $list = scandir($dir);
        foreach ($list as $key => $val) {
            if ($val == '..' || $val == '.') {
                continue;
            }
            if (is_file($dir . $val)) {
                $fileArr = $fileArr . '{"text":"' . $val . '","src":"' . $dir . $val . '"},';
            } else {
                $files = $this -> getNextTree($dir . $val . '/');
                $dirArr = $dirArr . '{"text":"' . $val . '","src":"' . $dir . $val . '","children":[' . $files . '],"state":"closed"},';
            }
        }
        $fileArr = $dirArr . $fileArr;
        $fileArr = substr($fileArr, 0, strlen($fileArr) - 1);
        return $fileArr;
    }

    //改变图片大小
    //依照像素进行转化
    public function resizejpg($imgsrc, $imgdst, $imgWidth, $imgHeight)
    {

        //$imgsrc 原路径
        //$imgdst 目标路径
        //$imgwidth要改变的宽度       $imgheight要改变的高度
        $arr = getimagesize($imgsrc);
        //取得源图片的宽度、高度值
        function_exists('exif_imagetype') or die('请安装exif拓展');
        $imgType = exif_imagetype($imgsrc);
        //需要安装exif拓展

        //  echo $imgType;die();
        //gif
        if ($imgType == 1) {
            $imgsrc = imagecreatefromgif($imgsrc);
        //根据路径创建图片控件，需要安装gd拓展
        } elseif ($imgType == 2) {
            header("Content-type: image/jpg");
            // Create image and define colors
            $imgsrc = imagecreatefromjpeg($imgsrc);
        //根据路径创建图片控件
        }
        //png
        elseif ($imgType == 3) {
            header("Content-type: image/png");
            // Create image and define colors
            $imgsrc = imagecreatefrompng($imgsrc);
        //根据路径创建图片控件
        } else {
            die();
        }
        //  $imgsrc=this->imagecreatefrompng();
        $image = imagecreatetruecolor($imgWidth, $imgHeight);

        if ($imgType == 3) {
            $color = imagecolorallocate($image, 255, 255, 255);
            imagefill($image, 0, 0, $color);
            imagecolortransparent($image, $color);
        }
        //根据宽高创建一个彩色的底图，作为目标图像
        imagecopyresampled($image, $imgsrc, 0, 0, 0, 0, $imgWidth, $imgHeight, $arr[0], $arr[1]);
        //		$this -> log($arr[0] . $arr[1]);

        if ($imgType == 1) {
            imagegif($image, $imgdst);
        } elseif ($imgType == 2) {
            imagejpeg($image, $imgdst, 100);
        }
        //png
        elseif ($imgType == 3) {
            imagepng($image, $imgdst);
        }
        //imagejpeg($res,$file_name_dest, $quality);    //测试时必须另外定义一个文件路径才可以
        imagedestroy($image);
    }

    //获取文件后缀
    public function get_ext($file_name)
    {
        $a = explode('.', $file_name);
        return array_pop($a);
        //获取数组最后一条数据
        //用.号对字符串进行分组
    }

    //获取"/"后的字符串
    public function get_last($file_name)
    {
        $a = explode('/', $file_name);
        return array_pop($a);
        //获取数组最后一条数据
        //用／号对字符串进行分组
    }

    //判断文件是否存在
    public function FileExist($filename)
    {
        if (file_exists($filename)) {#查看文件是否存在于网站目录
            return true;
        } else {
            return false;
        }
    }

    /*
     * 验证登陆
     * ses里保存了用户的id、nm、pw
     *
     * $type
     * 0：跳转；1：获取id
     *
     */

    public function VerifyLogin($type = 0)
    {
        global $_df;


        $login = getSession($this -> data["ses"]);

        if (!empty($login)) {
            $id = $login[0];
            $nm = hexToStr($login[1]);
            $pw = hexToStr($login[2]);
            if ($type == 'all') {
                return array($id, $nm, $pw);
            }
            $user = show_first('df', ['nm'=>$nm]);

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

    //将文件保存在根目录下
    //$this->log("{$url}<br>");
    //	   $this->log(json_encode($this->message)."<br>",7);    //写入数组
    public function log($str, $file = "log")
    {
        $myfile = fopen(ROOT . "{$file}.htm", "w") or die("Unable to open file!");
        fwrite($myfile, $str);
        fclose($myfile);
    }

    /*
     * 将图片对象保存到服务器
     *
     * @param  [Base64] $base64_image_content [要保存的Base64]
     * @param  [目录] $path [要保存的路径]
     */
    public function base64_image_content($base64_image_content, $path)
    {
        $dir = "upload/base64/";
        $err = 'error';
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];
            //      echo $new_file;
            if (!file_exists($dir)) {
                //检查是否有该文件夹，如果没有就创建，并给予读写权限
                mkdir($dir, 0700);
            }
            $new_file = $dir . $path . ".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                return '/' . $new_file;
            } else {
                return $err;
            }
        } else {
            return $err;
        }
    }

    /*wx公众号状态
     * id不为空就设置缓存
     * id为空，有缓存就读取缓存，没有就读取wx第一条数据
     */
    public function WxAc($id = '')
    {
        if (isset($_GET['WxId'])) {
            return $_GET['WxId'];
        }
        if ($id != '') {
            SetSession('wx', $id);
            return $id;
        }
        $id = getSession('wx');
        if ($id == "") {//缓存不存在就读数据库
            $dt = show("wx", -1);
            $rt = $dt[0][0];
        } else {//存在就直接返回
            $rt = $id;
        }
        return $rt;
    }

    //分割字符串
    //Split("1|2|3","|");
    public function Split($str, $char)
    {
        $rt = explode($char, $str);
        return $rt;
    }

    //判断表是否存在
    public function TableExist($table = 'cache')
    {
        global $db;
        $result = $db -> query("SHOW TABLES LIKE '" . $table . "'");
        $row = $result -> fetch_all();
        if (!count($row)) {
            die("Table does not exist<br><a href='create.php'>创建数据库</a>");
        }
    }

    //获取html中的所有图片的地址，生成一个数组
    public function getImage($other, $arry = 0)
    {
        $preg = '/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i';
        //匹配img标签的正则表达式
        preg_match_all($preg, $other, $allImg);
        //这里匹配所有的img
        //var_dump($allImg);
        if ($arry) {
            return $allImg;
        } else {
            $rt = "";
            foreach ($allImg[1] as $item) {
                $rt = $rt . '"' . $item . '",';
            }
            $rt = substr($rt, 0, strlen($rt) - 1);
            return $rt;
        }
    }

    //生成一个指定大小的数组
    public function DefineArr($a)
    {
        $array = array();
        for ($i = 0; $i < $a; $i++) {
            $array[$i] = $i;
        }
        return $array;
    }

    //收集用户信息
    public function col_user_info()
    {
        global $_df;
        $db = 'home_user_info';
        $user = show_first($db, ['ip'=>IP]);
        if ($user) {
            $dt = array('browser' => $_SERVER['HTTP_USER_AGENT'], 'hits' => $user['hits'] + 1, 'time' => $_df['time']);
            update($db, $dt, $user['Id']);
        } else {
            $dt = array('ip' => IP, 'browser' => $_SERVER['HTTP_USER_AGENT'], 'hits' => 0, 'first_time' => $_df['time'], 'time' => $_df['time']);
            update($db, $dt);
        }
    }
}

/**
 * 图片压缩类：通过缩放来压缩。
 * 如果要保持源图比例，把参数$percent保持为1即可。
 * 即使原比例压缩，也可大幅度缩小。数码相机4M图片。也可以缩为700KB左右。如果缩小比例，则体积会更小。
 *
 * 结果：可保存、可直接显示。
 */
class imgcompress
{
    private $src;
    private $image;
    private $imageinfo;
    private $percent = 0.5;

    /**
     * 图片压缩
     * @param $src 源图
     * @param float $percent  压缩比例
     */
    public function __construct($src, $percent = 1)
    {
        $this -> src = $src;
        $this -> percent = $percent;
    }

    /** 高清压缩图片
     * @param string $saveName  提供图片名（可不带扩展名，用源图扩展名）用于保存。或不提供文件名直接显示
     */
    public function compressImg($saveName = '')
    {
        $this -> _openImage();
        if (!empty($saveName)) {
            $this -> _saveImage($saveName);
        }
        //保存
        else {
            $this -> _showImage();
        }
    }

    /**
     * 内部：打开图片
     */
    private function _openImage()
    {
        list($width, $height, $type, $attr) = getimagesize($this -> src);
        $this -> imageinfo = array('width' => $width, 'height' => $height, 'type' => image_type_to_extension($type, false), 'attr' => $attr);
        $fun = "imagecreatefrom" . $this -> imageinfo['type'];
        $this -> image = $fun($this -> src);
        $this -> _thumpImage();
    }

    /**
     * 内部：操作图片
     */
    private function _thumpImage()
    {
        $new_width = $this -> imageinfo['width'] * $this -> percent;
        $new_height = $this -> imageinfo['height'] * $this -> percent;
        $image_thump = imagecreatetruecolor($new_width, $new_height);
        //将原图复制带图片载体上面，并且按照一定比例压缩,极大的保持了清晰度
        imagecopyresampled($image_thump, $this -> image, 0, 0, 0, 0, $new_width, $new_height, $this -> imageinfo['width'], $this -> imageinfo['height']);
        imagedestroy($this -> image);
        $this -> image = $image_thump;
    }

    /**
     * 输出图片:保存图片则用saveImage()
     */
    private function _showImage()
    {
        header('Content-Type: image/' . $this -> imageinfo['type']);
        $funcs = "image" . $this -> imageinfo['type'];
        $funcs($this -> image);
    }

    /**
     * 保存图片到硬盘：
     * @param  string $dstImgName  1、可指定字符串不带后缀的名称，使用源图扩展名 。2、直接指定目标图片名带扩展名。
     */
    private function _saveImage($dstImgName)
    {
        if (empty($dstImgName)) {
            return false;
        }
        $allowImgs = array('.jpg', '.jpeg', '.png', '.bmp', '.wbmp', '.gif');
        //如果目标图片名有后缀就用目标图片扩展名 后缀，如果没有，则用源图的扩展名
        $dstExt = strrchr($dstImgName, ".");
        $sourseExt = strrchr($this -> src, ".");
        if (!empty($dstExt)) {
            $dstExt = strtolower($dstExt);
        }
        if (!empty($sourseExt)) {
            $sourseExt = strtolower($sourseExt);
        }

        //有指定目标名扩展名
        if (!empty($dstExt) && in_array($dstExt, $allowImgs)) {
            $dstName = $dstImgName;
        } elseif (!empty($sourseExt) && in_array($sourseExt, $allowImgs)) {
            $dstName = $dstImgName . $sourseExt;
        } else {
            $dstName = $dstImgName . $this -> imageinfo['type'];
        }
        $funcs = "image" . $this -> imageinfo['type'];
        $funcs($this -> image, $dstName);
    }

    /**
     * 销毁图片
     */
    public function __destruct()
    {
        imagedestroy($this -> image);
    }
}
