<?php

declare(strict_types=1);

namespace tests\fixtures;

use yii\test\ActiveFixture;

class UserFixture extends ActiveFixture
{
    public $modelClass = 'app\models\User';

    public $dataFile = '@tests/fixtures/data/user.php';
}
