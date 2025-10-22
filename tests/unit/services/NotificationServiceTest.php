<?php

declare(strict_types=1);

namespace tests\unit\services;

use app\common\services\NotificationService;
use app\common\repositories\SubscriptionRepository;
use app\models\Book;
use app\models\NotificationQueue;
use tests\fixtures\BookFixture;
use tests\fixtures\AuthorFixture;
use tests\fixtures\SubscriptionFixture;
use Codeception\Test\Unit;

class NotificationServiceTest extends Unit
{
    private NotificationService $service;

    public function _fixtures(): array
    {
        return [
            'books' => BookFixture::class,
            'authors' => AuthorFixture::class,
            'subscriptions' => SubscriptionFixture::class,
        ];
    }

    protected function _before(): void
    {
        parent::_before();

        $repository = new SubscriptionRepository();
        $this->service = new NotificationService($repository);
    }

    public function testCreateNotificationsForBook(): void
    {
        $book = $this->tester->grabFixture('books', 'book1');

        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => $book->id,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        $book->refresh();

        $count = $this->service->createNotificationsForBook($book);

        $this->assertGreaterThan(0, $count);

        $notifications = NotificationQueue::find()
            ->where(['book_id' => $book->id])
            ->all();

        $this->assertCount($count, $notifications);

        foreach ($notifications as $notification) {
            $this->assertEquals(NotificationQueue::STATUS_PENDING, $notification->status);
            $this->assertNotEmpty($notification->message);
        }
    }

    public function testCreateNotificationsForBookWithNoAuthors(): void
    {
        $book = $this->tester->grabFixture('books', 'book1');

        $count = $this->service->createNotificationsForBook($book);

        $this->assertEquals(0, $count);
    }

    public function testCreateNotificationsForBookWithNoSubscribers(): void
    {
        $book = $this->tester->grabFixture('books', 'book1');

        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => $book->id,
            'author_id' => 3,
            'created_at' => time(),
        ])->execute();

        $book->refresh();

        $count = $this->service->createNotificationsForBook($book);

        $this->assertEquals(0, $count);
    }

    public function testNoDuplicateNotifications(): void
    {
        $book = $this->tester->grabFixture('books', 'book1');

        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => $book->id,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        $book->refresh();

        $firstCount = $this->service->createNotificationsForBook($book);
        $secondCount = $this->service->createNotificationsForBook($book);

        $this->assertEquals(0, $secondCount, 'Second call should not create duplicates');

        $totalNotifications = NotificationQueue::find()
            ->where(['book_id' => $book->id])
            ->count();

        $this->assertEquals($firstCount, $totalNotifications);
    }

    public function testGetNotificationStats(): void
    {
        $stats = $this->service->getNotificationStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('processing', $stats);
        $this->assertArrayHasKey('sent', $stats);
        $this->assertArrayHasKey('failed', $stats);

        $this->assertIsInt($stats['pending']);
        $this->assertIsInt($stats['processing']);
        $this->assertIsInt($stats['sent']);
        $this->assertIsInt($stats['failed']);
    }
}
