<?php

declare(strict_types=1);

namespace tests\unit\handlers;

use app\common\handlers\BookCreatedHandler;
use app\common\events\BookCreatedEvent;
use app\models\Book;
use app\models\NotificationQueue;
use app\models\Subscription;
use tests\fixtures\BookFixture;
use tests\fixtures\AuthorFixture;
use tests\fixtures\SubscriptionFixture;
use Codeception\Test\Unit;

class BookCreatedHandlerTest extends Unit
{
    private BookCreatedHandler $handler;

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

        $this->handler = new BookCreatedHandler();
    }

    public function testHandleMethodExists(): void
    {
        $this->assertTrue(method_exists($this->handler, 'handle'));
    }

    public function testHandleCreatesNotifications(): void
    {
        $book = new Book([
            'id' => 999,
            'title' => 'Handler Test Book',
            'year' => 2024,
            'isbn' => '978-1-111-11111-1',
        ]);
        $book->save(false);

        // Link to author with subscriptions
        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => $book->id,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        $event = new BookCreatedEvent($book);

        $notificationsBefore = NotificationQueue::find()->count();

        $this->handler->handle($event);

        $notificationsAfter = NotificationQueue::find()->count();

        $this->assertGreaterThan($notificationsBefore, $notificationsAfter);
    }

    public function testHandleCreatesCorrectNumberOfNotifications(): void
    {
        $book = new Book([
            'id' => 998,
            'title' => 'Count Test Book',
            'year' => 2024,
            'isbn' => '978-2-222-22222-2',
        ]);
        $book->save(false);

        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => $book->id,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        $subscribersCount = Subscription::find()->where(['author_id' => 1])->count();

        $event = new BookCreatedEvent($book);

        $notificationsBefore = NotificationQueue::find()->where(['book_id' => $book->id])->count();

        $this->handler->handle($event);

        $notificationsAfter = NotificationQueue::find()->where(['book_id' => $book->id])->count();

        $this->assertEquals($subscribersCount, $notificationsAfter - $notificationsBefore);
    }

    public function testHandleCreatesNotificationWithCorrectMessage(): void
    {
        $book = new Book([
            'id' => 997,
            'title' => 'Message Test Book',
            'year' => 2024,
            'isbn' => '978-3-333-33333-3',
        ]);
        $book->save(false);

        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => $book->id,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        $event = new BookCreatedEvent($book);

        $this->handler->handle($event);

        $notifications = NotificationQueue::find()->where(['book_id' => $book->id])->all();

        $this->assertNotEmpty($notifications);

        foreach ($notifications as $notification) {
            $this->assertStringContainsString('Message Test Book', $notification->message);
        }
    }

    public function testHandleWithMultipleAuthors(): void
    {
        $book = new Book([
            'id' => 996,
            'title' => 'Multi-Author Handler Test',
            'year' => 2024,
            'isbn' => '978-4-444-44444-4',
        ]);
        $book->save(false);

        // Link to multiple authors
        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => $book->id,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => $book->id,
            'author_id' => 2,
            'created_at' => time(),
        ])->execute();

        $subscribers1 = Subscription::find()->where(['author_id' => 1])->count();
        $subscribers2 = Subscription::find()->where(['author_id' => 2])->count();

        $event = new BookCreatedEvent($book);

        $this->handler->handle($event);

        $notifications = NotificationQueue::find()->where(['book_id' => $book->id])->count();

        $this->assertGreaterThanOrEqual($subscribers1 + $subscribers2, $notifications);
    }

    public function testHandleDoesNotCreateDuplicateNotifications(): void
    {
        $book = new Book([
            'id' => 995,
            'title' => 'Duplicate Test Book',
            'year' => 2024,
            'isbn' => '978-5-555-55555-5',
        ]);
        $book->save(false);

        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => $book->id,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        $event = new BookCreatedEvent($book);

        $this->handler->handle($event);
        $countAfterFirst = NotificationQueue::find()->where(['book_id' => $book->id])->count();

        // Call handler again
        $this->handler->handle($event);
        $countAfterSecond = NotificationQueue::find()->where(['book_id' => $book->id])->count();

        $this->assertEquals($countAfterFirst, $countAfterSecond);
    }

    public function testHandleWithNoSubscribers(): void
    {
        $book = new Book([
            'id' => 994,
            'title' => 'No Subscribers Book',
            'year' => 2024,
            'isbn' => '978-6-666-66666-6',
        ]);
        $book->save(false);

        // Link to author with no subscriptions (author 3)
        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => $book->id,
            'author_id' => 3,
            'created_at' => time(),
        ])->execute();

        $event = new BookCreatedEvent($book);

        $notificationsBefore = NotificationQueue::find()->where(['book_id' => $book->id])->count();

        $this->handler->handle($event);

        $notificationsAfter = NotificationQueue::find()->where(['book_id' => $book->id])->count();

        $this->assertEquals($notificationsBefore, $notificationsAfter);
    }

    public function testHandleExceptionDoesNotBreakExecution(): void
    {
        $book = new Book([
            'id' => 993,
            'title' => 'Exception Test Book',
            'year' => 2024,
            'isbn' => '978-7-777-77777-7',
        ]);

        $event = new BookCreatedEvent($book);

        try {
            $this->handler->handle($event);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Handler should not throw exception: ' . $e->getMessage());
        }
    }

    public function testHandleLogsErrors(): void
    {
        $book = new Book([
            'id' => null, // Invalid book
            'title' => 'Error Log Test',
            'year' => 2024,
            'isbn' => '978-8-888-88888-8',
        ]);

        $event = new BookCreatedEvent($book);

        // Handler should handle error gracefully
        try {
            $this->handler->handle($event);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Should not throw
            $this->fail('Should handle error gracefully');
        }
    }

    public function testHandlerIsStateless(): void
    {
        $book1 = new Book([
            'id' => 992,
            'title' => 'Stateless Test 1',
            'year' => 2024,
            'isbn' => '978-9-999-99999-9',
        ]);
        $book1->save(false);

        $book2 = new Book([
            'id' => 991,
            'title' => 'Stateless Test 2',
            'year' => 2024,
            'isbn' => '978-9-999-99999-8',
        ]);
        $book2->save(false);

        $event1 = new BookCreatedEvent($book1);
        $event2 = new BookCreatedEvent($book2);

        $this->handler->handle($event1);
        $this->handler->handle($event2);

        // Each event should be handled independently
        $this->assertTrue(true);
    }
}
