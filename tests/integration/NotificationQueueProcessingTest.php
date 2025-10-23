<?php

declare(strict_types=1);

namespace tests\integration;

use app\models\NotificationQueue;
use tests\fixtures\NotificationQueueFixture;
use tests\fixtures\SubscriptionFixture;
use tests\fixtures\BookFixture;
use Codeception\Test\Unit;

class NotificationQueueProcessingTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'queue' => NotificationQueueFixture::class,
            'subscriptions' => SubscriptionFixture::class,
            'books' => BookFixture::class,
        ];
    }

    public function testFindReadyForSending(): void
    {
        $ready = NotificationQueue::findReadyForSending(10)->all();

        $this->assertIsArray($ready);

        foreach ($ready as $notification) {
            $this->assertEquals(NotificationQueue::STATUS_PENDING, $notification->status);
            $this->assertLessThan($notification->max_retries, $notification->retry_count);

            if ($notification->next_retry_at !== null) {
                $this->assertLessThanOrEqual(time(), $notification->next_retry_at);
            }
        }
    }

    public function testProcessingWorkflow(): void
    {
        $notification = NotificationQueue::findReadyForSending(1)->one();

        if ($notification === null) {
            $this->markTestSkipped('No notifications ready for sending');
        }

        $originalRetryCount = $notification->retry_count;
        $notification->markAsProcessing();

        $this->assertEquals(NotificationQueue::STATUS_PROCESSING, $notification->status);
        $this->assertEquals($originalRetryCount + 1, $notification->retry_count);

        $notification->markAsSent();

        $this->assertEquals(NotificationQueue::STATUS_SENT, $notification->status);
        $this->assertNotNull($notification->sent_at);
        $this->assertNull($notification->error_message);
    }

    public function testRetryMechanism(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');

        $notification->retry_count = 0;
        $notification->save(false);

        $notification->markAsProcessing();
        $notification->markAsFailedWithRetry('Temporary error 1');

        $this->assertEquals(NotificationQueue::STATUS_PENDING, $notification->status);
        $this->assertNotNull($notification->next_retry_at);
        $firstRetryTime = $notification->next_retry_at;

        $notification->markAsProcessing();
        $notification->markAsFailedWithRetry('Temporary error 2');

        $this->assertGreaterThan($firstRetryTime, $notification->next_retry_at);
    }

    public function testMaxRetriesExceeded(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');

        $notification->retry_count = 2;
        $notification->max_retries = 3;
        $notification->save(false);

        $notification->markAsProcessing();
        $notification->markAsFailedWithRetry('Final error');

        $this->assertEquals(NotificationQueue::STATUS_FAILED, $notification->status);
        $this->assertNull($notification->next_retry_at);
        $this->assertFalse($notification->canRetry());
    }

    public function testBatchProcessing(): void
    {
        $limit = 5;
        $notifications = NotificationQueue::findReadyForSending($limit)->all();

        $processedCount = 0;

        foreach ($notifications as $notification) {
            $notification->markAsProcessing();

            $success = (bool)random_int(0, 1);

            if ($success) {
                $notification->markAsSent();
            } else {
                $notification->markAsFailedWithRetry('Simulated error');
            }

            $processedCount++;
        }

        $this->assertLessThanOrEqual($limit, $processedCount);
    }

    public function testNoDuplicateProcessing(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');

        $notification->markAsProcessing();

        $ready = NotificationQueue::findReadyForSending(100)->all();

        foreach ($ready as $readyNotification) {
            $this->assertNotEquals($notification->id, $readyNotification->id);
        }
    }
}
