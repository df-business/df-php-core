<?php
defined('INIT') or exit('Access Denied');
//----------------------------------------------全局参数

// 默认模板
define('THEME_HOMEPAGE', env('THEME_HOMEPAGE','homepage'));
define('THEME_ADMIN', env('THEME_ADMIN','admin'));



#开发模式开关（调试完之后关闭此开关，否则有泄露网站结构的风险）
define('DEV', env('DEV',1));
define('SERVER', env('SERVER', 'localhost'));
define('ACC', env('ACC', 'dfphp_dfer_site')'');
define('PWD', env('PWD', 'mMHBCAimbKKjPP67'));
define('DATABASE', env('DATABASE', 'dfphp_dfer_site'));

//email模块的开关
define('EMAIL_ENABLE', false);
//当前框架需要的最低php版本
define('DF_PHP_VER',env('DF_PHP_VER',8));
//seo优化模式
define('SEO', env('SEO',1));
//PC页面、手机页面分离开关
define('WAP_PAGE_ENABLE', env('WAP_PAGE_ENABLE',1));
// 3*24小时
define('SESSION_EXPIRES', env('SESSION_EXPIRES',3*24*3600));



/*
 * 自动将页面元素的http升级为https,默认以http方式访问网页无法获取https的数据，加入这个代码之后必须使用https的方式才能正常访问网页
 * 在https下访问http会被认为是不安全的操作，Safari会直接屏蔽该网页
 * 为了防止被屏蔽，应该让http自动升级为https
 */
define('SSL_STATE', empty($_SERVER['HTTPS']) ? false : $_SERVER['HTTPS']);
define('SSL', SSL_STATE ? 'https' : 'http');
$ssl = SSL_STATE;
if ($ssl) {
	header("Content-Security-Policy: upgrade-insecure-requests");
	define('SITE', 'https://' . $_SERVER['HTTP_HOST'] . '/');
	//当前域名
} else {
	//header("Content-Security-Policy: img-src *	");
	define('SITE', 'http://' . $_SERVER['HTTP_HOST'] . '/');
	//当前域名
}
//当前页面完整url
define('URL', htmlspecialchars_decode(SITE . 'index.php?' . htmlspecialchars($_SERVER['QUERY_STRING'])));
