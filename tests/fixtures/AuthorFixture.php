<?php

declare(strict_types=1);

namespace tests\fixtures;

use yii\test\ActiveFixture;

class AuthorFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Author';

    public $dataFile = '@tests/fixtures/data/author.php';
}
