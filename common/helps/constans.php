<?php
namespace common\helps;

define('M_ROOT', substr(dirname(__FILE__), 0, -7));//根目录地址
define('CODETABLEDIR', M_ROOT.'../encoding/');
define('MCHARSET', 'utf-8');//系统页面默认字符集, 可选 'gbk', 'big5', 'utf-8'
define('ONCEUPDATECHAPTER', 100);//一次刷新书籍个数
define('PAGE_SIZE_TWELVE', 12); //分页个数
define('PAGE_SIZE_HUNDRED', 100); //分页个数
define('PAGE_SIZE_THOUSAND', 10000); //分页个数
define('IMGURLTOP', 'http://img.361trees.com/');//图片网址链接

define('DESC', 1); //SQL排序，降序
define('ASC', 2); //SQL排序，升序

define('HALF_HOUSE_TIME', 1800);
define('ONE_HOUSE_TIME', 3600);
define('ONE_DAY_TIME', 86400);
define('ONE_MONTH_TIME', 2592000);

