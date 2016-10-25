<?php
$params = array_merge(
		require(__DIR__ . '/sphinxServer-local.php')
);

if(YII_ENV_TEST) {
    return [
        'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
        'components' => [
            'cache' => [
                'class' => 'yii\caching\FileCache',
            ]
        ]
    ];
} else {
    return [
        'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
        'components' => [
            'db' => [
                'class' => 'yii\db\Connection',
                'dsn' => 'mysql:host=127.0.0.1;dbname=zhuabook3',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8',
            ],
			'mongodb' => [
				'class' => '\yii\mongodb\Connection',
				'dsn' => 'mongodb://yihangbook:yihangbook@127.0.0.1:27017/xzbook',
			],
            'cache' => [
                'class' => 'yii\caching\FileCache',
            ],
            'mailer' => [
                'class' => 'yii\swiftmailer\Mailer',
                'viewPath' => '@common/mail',
                // send all mails to a file by default. You have to set
                // 'useFileTransport' to false and configure a transport
                // for the mailer to send real emails.
                'useFileTransport' => true,
            ],
        ],
    	'params' => $params,
    ];
}
