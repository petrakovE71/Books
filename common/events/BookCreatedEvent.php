<?php

declare(strict_types=1);

namespace app\common\events;

use yii\base\Event;
use app\models\Book;

class BookCreatedEvent extends Event
{
    public const EVENT_NAME = 'bookCreated';

    /**
     * @var Book
     */
    public Book $book;
}
