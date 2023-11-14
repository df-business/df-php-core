<?php
defined('INIT') or exit('Access Denied');
//----------------------------------------------全局参数

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
//当前框架需要的最低php版本
define('DF_PHP_VER', env('DF_PHP_VER', 8));
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
