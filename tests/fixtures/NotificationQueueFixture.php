<?php

declare(strict_types=1);

namespace tests\fixtures;

use yii\test\ActiveFixture;

class NotificationQueueFixture extends ActiveFixture
{
    public $modelClass = 'app\models\NotificationQueue';

    public $dataFile = '@tests/fixtures/data/notification_queue.php';

    public $depends = [
        SubscriptionFixture::class,
        BookFixture::class,
    ];
}
