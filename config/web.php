<?php

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'language' => 'ru-RU',
    'charset' => 'UTF-8',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'SpB6N0Lg8ofFnqW4U1UYZ4LtuxtEjl-_',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
            'cache' => 'cache',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'books' => 'book/index',
                'book/<id:\d+>' => 'book/view',
                'authors' => 'author/index',
                'author/<id:\d+>' => 'author/view',
                'subscribe' => 'subscription/create',
                'report' => 'report/index',
            ],
        ],
        // Repositories
        'bookRepository' => [
            'class' => 'app\common\repositories\BookRepository',
        ],
        'subscriptionRepository' => [
            'class' => 'app\common\repositories\SubscriptionRepository',
        ],
        // Services
        'bookService' => [
            'class' => 'app\common\services\BookService',
            'bookRepository' => function() { return Yii::$app->get('bookRepository'); },
        ],
        'subscriptionService' => [
            'class' => 'app\common\services\SubscriptionService',
            'subscriptionRepository' => function() { return Yii::$app->get('subscriptionRepository'); },
        ],
        'notificationService' => [
            'class' => 'app\common\services\NotificationService',
            'subscriptionRepository' => function() { return Yii::$app->get('subscriptionRepository'); },
        ],
        // SMS Provider
        'smsProvider' => [
            'class' => 'app\components\sms\SmsPilotProvider',
            'apiKey' => $_ENV['SMS_PILOT_API_KEY'] ?? 'XXXXXXXXXXXXYYYYYYYYYYYYZZZZZZZZXXXXXXXXXXXXYYYYYYYYYYYYZZZZZZZZ',
            'testMode' => filter_var($_ENV['SMS_TEST_MODE'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ],
        // SMS Service
        'smsService' => [
            'class' => 'app\components\sms\SmsService',
            'provider' => function() { return Yii::$app->get('smsProvider'); },
        ],
    ],
    'on beforeRequest' => function () {
        // Register event handler for Book created
        \yii\base\Event::on(
            \app\models\Book::class,
            \app\common\events\BookCreatedEvent::EVENT_NAME,
            [new \app\common\handlers\BookCreatedHandler(), 'handle']
        );
    },
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
