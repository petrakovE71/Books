<?php

declare(strict_types=1);

namespace tests\fixtures;

use yii\test\ActiveFixture;

class SubscriptionFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Subscription';

    public $dataFile = '@tests/fixtures/data/subscription.php';

    public $depends = [AuthorFixture::class];
}
