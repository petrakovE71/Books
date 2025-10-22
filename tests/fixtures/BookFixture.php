<?php

declare(strict_types=1);

namespace tests\fixtures;

use yii\test\ActiveFixture;

class BookFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Book';

    public $dataFile = '@tests/fixtures/data/book.php';

    public $depends = [AuthorFixture::class];
}
