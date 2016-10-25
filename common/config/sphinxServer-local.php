<?php
/**
 * sphinx配置文件
 */
return [
    'sphinxServer' => [
        //直营城市非聚合售房
		'bookinfo' => [
            'port' => '9897',
            'server' => '120.76.123.86',
            'indexName' => 'bookinfo'
        ],
		'booksource' => [
			'port' => '9898',
			'server' => '120.76.123.86',
			'indexName' => 'booksource'
		],
		'chapters' => [
			'port' => '9903',
			'server' => '120.76.123.86',
			'indexName' => 'chapters'
		],
    ]
];
