<?php

declare(strict_types=1);

namespace app\common\services;

use Yii;
use app\models\Book;
use app\models\NotificationQueue;
use app\models\Subscription;
use app\common\repositories\SubscriptionRepository;
use app\common\dto\NotificationDto;
use app\common\exceptions\NotificationException;

final class NotificationService
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
    ) {}

    /**
     * @param Book $book
     * @return int
     * @throws NotificationException
     */
    public function createNotificationsForBook(Book $book): int
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $authors = $book->authors;

            if (empty($authors)) {
                Yii::warning("Book #{$book->id} has no authors, skipping notifications", __METHOD__);
                $transaction->commit();
                return 0;
            }

            $authorIds = array_map(fn($author) => $author->id, $authors);

            $subscriptions = $this->subscriptionRepository->findByAuthorIds($authorIds);

            if (empty($subscriptions)) {
                Yii::info("No subscriptions found for book #{$book->id} authors", __METHOD__);
                $transaction->commit();
                return 0;
            }

            $createdCount = 0;

            foreach ($subscriptions as $subscription) {
                /** @var Subscription $subscription */

                $exists = NotificationQueue::find()
                    ->where([
                        'subscription_id' => $subscription->id,
                        'book_id' => $book->id,
                    ])
                    ->exists();

                if ($exists) {
                    continue;
                }

                $message = $this->buildNotificationMessage($book, $subscription);

                $dto = new NotificationDto(
                    subscriptionId: $subscription->id,
                    bookId: $book->id,
                    phone: $subscription->phone,
                    message: $message
                );

                $this->createNotificationQueue($dto);
                $createdCount++;
            }

            $transaction->commit();

            Yii::info(
                "Created {$createdCount} notifications for book #{$book->id}",
                __METHOD__
            );

            return $createdCount;
        } catch (\Throwable $e) {
            $transaction->rollBack();

            Yii::error(
                "Failed to create notifications for book #{$book->id}: {$e->getMessage()}",
                __METHOD__
            );

            throw new NotificationException(
                "Failed to create notifications: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * @param NotificationDto $dto
     * @return NotificationQueue
     * @throws \yii\db\Exception
     */
    private function createNotificationQueue(NotificationDto $dto): NotificationQueue
    {
        $queue = new NotificationQueue();
        $queue->subscription_id = $dto->subscriptionId;
        $queue->book_id = $dto->bookId;
        $queue->phone = $dto->phone;
        $queue->message = $dto->message;
        $queue->status = NotificationQueue::STATUS_PENDING;

        if (!$queue->save()) {
            throw new \yii\db\Exception('Failed to save notification queue: ' . json_encode($queue->errors));
        }

        return $queue;
    }

    /**
     * @param Book $book
     * @param Subscription $subscription
     * @return string
     */
    private function buildNotificationMessage(Book $book, Subscription $subscription): string
    {
        $authorName = $subscription->author->fio ?? 'author';

        $message = "New book by {$authorName}: \"{$book->title}\" ({$book->year})";

        if (mb_strlen($message) > 160) {
            $message = mb_substr($message, 0, 157) . '...';
        }

        return $message;
    }

    /**
     * @return array
     */
    public function getNotificationStats(): array
    {
        return [
            'pending' => NotificationQueue::find()->where(['status' => NotificationQueue::STATUS_PENDING])->count(),
            'processing' => NotificationQueue::find()->where(['status' => NotificationQueue::STATUS_PROCESSING])->count(),
            'sent' => NotificationQueue::find()->where(['status' => NotificationQueue::STATUS_SENT])->count(),
            'failed' => NotificationQueue::find()->where(['status' => NotificationQueue::STATUS_FAILED])->count(),
        ];
    }
}
