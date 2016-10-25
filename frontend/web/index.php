<?php

date_default_timezone_set('Asia/Shanghai');
defined('YII_DEBUG') or define('YII_DEBUG', true);
//prod 生产环境 dev开发环境
defined('YII_ENV') or define('YII_ENV', 'dev');
//defined('YII_ENV') or define('YII_ENV', 'prod');

require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../../common/config/bootstrap.php');
require(__DIR__ . '/../config/bootstrap.php');
require(__DIR__ . '/../../common/helps/constans.php');

if(YII_ENV_DEV) {
        $config = yii\helpers\ArrayHelper::merge(
                require(__DIR__ . '/../../common/config/main-local.php'),
                require(__DIR__ . '/../config/main-local.php')
        );
} else {
        $config = yii\helpers\ArrayHelper::merge(
                require(__DIR__ . '/../../common/config/main.php'),
                require(__DIR__ . '/../config/main.php')
        );
}

$application = new yii\web\Application($config);
$application->run();
