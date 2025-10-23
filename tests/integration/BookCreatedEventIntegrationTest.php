<?php

declare(strict_types=1);

namespace tests\integration;

use app\common\services\BookService;
use app\common\repositories\BookRepository;
use app\common\dto\CreateBookDto;
use app\models\NotificationQueue;
use app\models\Subscription;
use tests\fixtures\AuthorFixture;
use tests\fixtures\SubscriptionFixture;
use Codeception\Test\Unit;

class BookCreatedEventIntegrationTest extends Unit
{
    private BookService $bookService;

    public function _fixtures(): array
    {
        return [
            'authors' => AuthorFixture::class,
            'subscriptions' => SubscriptionFixture::class,
        ];
    }

    protected function _before(): void
    {
        parent::_before();

        $repository = new BookRepository();
        $this->bookService = new BookService($repository);

        \Yii::$app->on(
            \app\common\events\BookCreatedEvent::EVENT_NAME,
            [\app\common\handlers\BookCreatedHandler::class, 'handle']
        );
    }

    public function testBookCreationTriggersEvent(): void
    {
        $dto = new CreateBookDto(
            title: 'Integration Test Book',
            year: 2024,
            isbn: '978-9-999-99999-9',
            authorIds: [1],
            description: 'Test event triggering'
        );

        $notificationsBefore = NotificationQueue::find()->count();

        $book = $this->bookService->createBook($dto);

        $this->assertNotNull($book->id);

        $notificationsAfter = NotificationQueue::find()->count();

        $this->assertGreaterThan($notificationsBefore, $notificationsAfter);

        $notifications = NotificationQueue::find()
            ->where(['book_id' => $book->id])
            ->all();

        $this->assertNotEmpty($notifications);

        foreach ($notifications as $notification) {
            $this->assertEquals(NotificationQueue::STATUS_PENDING, $notification->status);
            $this->assertStringContainsString($book->title, $notification->message);
        }
    }

    public function testMultipleAuthorsCreateMultipleNotifications(): void
    {
        \Yii::$app->db->createCommand()->insert('{{%subscription}}', [
            'id' => 100,
            'author_id' => 2,
            'name' => 'Test Subscriber',
            'phone' => '+79991111111',
            'created_at' => time(),
        ])->execute();

        $dto = new CreateBookDto(
            title: 'Multi-Author Book',
            year: 2024,
            isbn: '978-8-888-88888-8',
            authorIds: [1, 2],
            description: 'Multiple authors test'
        );

        $book = $this->bookService->createBook($dto);

        $notifications = NotificationQueue::find()
            ->where(['book_id' => $book->id])
            ->all();

        $subscribersForAuthor1 = Subscription::find()->where(['author_id' => 1])->count();
        $subscribersForAuthor2 = Subscription::find()->where(['author_id' => 2])->count();

        $expectedNotifications = $subscribersForAuthor1 + $subscribersForAuthor2;

        $this->assertGreaterThanOrEqual($expectedNotifications, count($notifications));
    }

    public function testNoNotificationsForBookWithoutSubscribers(): void
    {
        $dto = new CreateBookDto(
            title: 'No Subscribers Book',
            year: 2024,
            isbn: '978-7-777-77777-7',
            authorIds: [3],
            description: 'Author with no subscribers'
        );

        $notificationsBefore = NotificationQueue::find()->count();

        $book = $this->bookService->createBook($dto);

        $notificationsAfter = NotificationQueue::find()->count();

        $this->assertEquals($notificationsBefore, $notificationsAfter);

        $notifications = NotificationQueue::find()
            ->where(['book_id' => $book->id])
            ->all();

        $this->assertEmpty($notifications);
    }

    public function testEventHandlerDoesNotBreakBookCreation(): void
    {
        \Yii::$app->on(
            \app\common\events\BookCreatedEvent::EVENT_NAME,
            function() {
                throw new \Exception('Handler error');
            }
        );

        $dto = new CreateBookDto(
            title: 'Error Resilient Book',
            year: 2024,
            isbn: '978-6-666-66666-6',
            authorIds: [1]
        );

        $book = $this->bookService->createBook($dto);

        $this->assertNotNull($book->id);
    }
}
