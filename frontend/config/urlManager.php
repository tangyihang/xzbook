<?php
return [
    'enablePrettyUrl' => true, //路径化
    'showScriptName' => false, //隐藏入口脚本
    //'suffix' => '.htm', //假后缀
    'rules' => [
        'site/<action:\w+>' => 'site/<action>',
    	'gather/<action:\w+>' => 'gather/<action>',
//     	'<controller:\w+>/<action:\w+>' => '<controller>/<action>'
    ]
];

