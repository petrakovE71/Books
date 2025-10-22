<?php

declare(strict_types=1);

namespace app\common\handlers;

use Yii;
use app\common\events\BookCreatedEvent;
use app\common\services\NotificationService;
use yii\base\Component;

class BookCreatedHandler extends Component
{
    /**
     * Handle event
     *
     * @param BookCreatedEvent $event
     * @return void
     */
    public function handle(BookCreatedEvent $event): void
    {
        try {
            /** @var NotificationService $notificationService */
            $notificationService = Yii::$app->get('notificationService');

            $notificationService->createNotificationsForBook($event->book);

            Yii::info(
                "Notifications created for book #{$event->book->id} '{$event->book->title}'",
                __METHOD__
            );
        } catch (\Throwable $e) {
            Yii::error(
                "Failed to create notifications for book #{$event->book->id}: {$e->getMessage()}",
                __METHOD__
            );
        }
    }
}
