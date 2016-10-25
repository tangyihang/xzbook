<?php

$params = array_merge(
    require(__DIR__ . '/params-local.php'),
    require(__DIR__ . '/../../common/config/params-local.php')
);

return [
    'id' => 'm.5i5j.com',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'language' => 'zh-CN',
    'modules' => [
        'debug' => [
            'class' => 'yii\debug\Module'
        ],
        'gii' => [
            'class' => 'yii\gii\Module'
        ]
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => '-vwITyVuAGOtfwxPAjgA4c_EMsrkILaF'
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'profile', 'trace', 'info'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'view' => [
            'theme' => [
                'baseUrl' => '@web/themes/default',
                //后面会将前面的覆盖
                'pathMap' => [
                    '@frontend/views' => '@frontend/themes/black/views',
                    '@frontend/views' => '@frontend/themes/default/views',
                ],
            ]
        ],
        'urlManager' => require(__DIR__ . '/urlManager.php'),
    ],
    'params' => $params,
];
