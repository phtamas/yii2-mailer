<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';

new yii\web\Application([
    'id' => '',
    'basePath' => '',
    'aliases' => [
        '@tests' => __DIR__,
    ]
]);