<?php

declare(strict_types=1);

namespace tests\unit\models;

use app\models\NotificationQueue;
use tests\fixtures\NotificationQueueFixture;
use tests\fixtures\SubscriptionFixture;
use tests\fixtures\BookFixture;
use Codeception\Test\Unit;

class NotificationQueueTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'queue' => NotificationQueueFixture::class,
            'subscriptions' => SubscriptionFixture::class,
            'books' => BookFixture::class,
        ];
    }

    public function testValidation(): void
    {
        $notification = new NotificationQueue();

        $this->assertFalse($notification->validate());
        $this->assertArrayHasKey('subscription_id', $notification->errors);
        $this->assertArrayHasKey('book_id', $notification->errors);
        $this->assertArrayHasKey('phone', $notification->errors);
        $this->assertArrayHasKey('message', $notification->errors);
    }

    public function testDefaultValues(): void
    {
        $notification = new NotificationQueue([
            'subscription_id' => 1,
            'book_id' => 1,
            'phone' => '+79991234567',
            'message' => 'Test message',
        ]);

        $this->assertTrue($notification->validate());
        $this->assertEquals(NotificationQueue::STATUS_PENDING, $notification->status);
        $this->assertEquals(0, $notification->retry_count);
        $this->assertEquals(3, $notification->max_retries);
    }

    public function testStatusValidation(): void
    {
        $notification = new NotificationQueue([
            'subscription_id' => 1,
            'book_id' => 1,
            'phone' => '+79991234567',
            'message' => 'Test',
            'status' => 'invalid_status',
        ]);

        $this->assertFalse($notification->validate(['status']));
    }

    public function testMarkAsProcessing(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');

        $this->assertEquals(0, $notification->retry_count);
        $this->assertEquals(NotificationQueue::STATUS_PENDING, $notification->status);

        $result = $notification->markAsProcessing();

        $this->assertTrue($result);
        $this->assertEquals(1, $notification->retry_count);
        $this->assertEquals(NotificationQueue::STATUS_PROCESSING, $notification->status);
    }

    public function testMarkAsSent(): void
    {
        $notification = $this->tester->grabFixture('queue', 'processing');

        $this->assertNull($notification->sent_at);

        $result = $notification->markAsSent();

        $this->assertTrue($result);
        $this->assertEquals(NotificationQueue::STATUS_SENT, $notification->status);
        $this->assertNotNull($notification->sent_at);
        $this->assertNull($notification->error_message);
    }

    public function testMarkAsFailedWithRetry(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');

        $notification->retry_count = 1;
        $result = $notification->markAsFailedWithRetry('Test error');

        $this->assertTrue($result);
        $this->assertEquals(NotificationQueue::STATUS_PENDING, $notification->status);
        $this->assertEquals('Test error', $notification->error_message);
        $this->assertNotNull($notification->next_retry_at);
    }

    public function testMarkAsFailedWithRetryMaxRetriesExceeded(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');

        $notification->retry_count = 3;
        $result = $notification->markAsFailedWithRetry('Max retries exceeded');

        $this->assertTrue($result);
        $this->assertEquals(NotificationQueue::STATUS_FAILED, $notification->status);
        $this->assertNull($notification->next_retry_at);
    }

    public function testMarkAsPermanentlyFailed(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');

        $result = $notification->markAsPermanentlyFailed('Permanent error');

        $this->assertTrue($result);
        $this->assertEquals(NotificationQueue::STATUS_FAILED, $notification->status);
        $this->assertEquals('Permanent error', $notification->error_message);
        $this->assertNull($notification->next_retry_at);
    }

    public function testExponentialBackoff(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');

        $notification->retry_count = 1;
        $notification->markAsFailedWithRetry('Error 1');
        $firstRetryTime = $notification->next_retry_at;

        $notification->retry_count = 2;
        $notification->markAsFailedWithRetry('Error 2');
        $secondRetryTime = $notification->next_retry_at;

        $this->assertGreaterThan($firstRetryTime, $secondRetryTime);
    }

    public function testGetStatusLabel(): void
    {
        $pending = $this->tester->grabFixture('queue', 'pending');
        $this->assertNotEmpty($pending->getStatusLabel());

        $sent = $this->tester->grabFixture('queue', 'sent');
        $this->assertNotEmpty($sent->getStatusLabel());
    }

    public function testCanRetry(): void
    {
        $pending = $this->tester->grabFixture('queue', 'pending');
        $this->assertTrue($pending->canRetry());

        $failed = $this->tester->grabFixture('queue', 'failed');
        $this->assertFalse($failed->canRetry());

        $sent = $this->tester->grabFixture('queue', 'sent');
        $this->assertFalse($sent->canRetry());
    }

    public function testFindReadyForSending(): void
    {
        $ready = NotificationQueue::findReadyForSending(10)->all();

        $this->assertIsArray($ready);

        foreach ($ready as $notification) {
            $this->assertEquals(NotificationQueue::STATUS_PENDING, $notification->status);
            $this->assertLessThan($notification->max_retries, $notification->retry_count);
        }
    }
}
