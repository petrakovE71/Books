<?php

declare(strict_types=1);

namespace tests\unit\sms;

use app\common\services\SmsService;
use app\common\sms\SmsPilotProvider;
use app\models\NotificationQueue;
use Codeception\Test\Unit;

class SmsServiceTest extends Unit
{
    private SmsService $service;
    private $mockProvider;

    protected function _before(): void
    {
        parent::_before();

        $this->mockProvider = $this->createMock(SmsPilotProvider::class);
        $this->service = new SmsService($this->mockProvider);
    }

    public function testSendNotificationSuccess(): void
    {
        $notification = new NotificationQueue([
            'phone' => '+79991234567',
            'message' => 'Test message',
            'status' => NotificationQueue::STATUS_PENDING,
            'max_retries' => 3,
            'retry_count' => 0,
        ]);

        $this->mockProvider->expects($this->once())
            ->method('send')
            ->with('+79991234567', 'Test message')
            ->willReturn(true);

        $result = $this->service->sendNotification($notification);

        $this->assertTrue($result);
        $this->assertEquals(NotificationQueue::STATUS_SENT, $notification->status);
        $this->assertNotNull($notification->sent_at);
    }

    public function testSendNotificationFailure(): void
    {
        $notification = new NotificationQueue([
            'phone' => '+79991234567',
            'message' => 'Test message',
            'status' => NotificationQueue::STATUS_PENDING,
            'max_retries' => 3,
            'retry_count' => 0,
        ]);

        $this->mockProvider->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception('Provider error'));

        $result = $this->service->sendNotification($notification);

        $this->assertFalse($result);
        $this->assertEquals(NotificationQueue::STATUS_PENDING, $notification->status);
        $this->assertStringContainsString('Provider error', $notification->error_message);
    }

    public function testSendNotificationWithInvalidPhone(): void
    {
        $notification = new NotificationQueue([
            'phone' => 'invalid',
            'message' => 'Test message',
            'status' => NotificationQueue::STATUS_PENDING,
            'max_retries' => 3,
            'retry_count' => 0,
        ]);

        $this->mockProvider->expects($this->never())
            ->method('send');

        $result = $this->service->sendNotification($notification);

        $this->assertFalse($result);
        $this->assertNotEmpty($notification->error_message);
    }

    public function testSendNotificationUpdatesRetryCount(): void
    {
        $notification = new NotificationQueue([
            'phone' => '+79991234567',
            'message' => 'Test message',
            'status' => NotificationQueue::STATUS_PENDING,
            'max_retries' => 3,
            'retry_count' => 0,
        ]);

        $this->mockProvider->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception('Temporary error'));

        $this->service->sendNotification($notification);

        $this->assertGreaterThan(0, $notification->retry_count);
        $this->assertNotNull($notification->next_retry_at);
    }

    public function testSendNotificationMarksAsFailedWhenMaxRetriesExceeded(): void
    {
        $notification = new NotificationQueue([
            'phone' => '+79991234567',
            'message' => 'Test message',
            'status' => NotificationQueue::STATUS_PENDING,
            'max_retries' => 3,
            'retry_count' => 3,
        ]);

        $this->mockProvider->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception('Final error'));

        $result = $this->service->sendNotification($notification);

        $this->assertFalse($result);
        $this->assertEquals(NotificationQueue::STATUS_FAILED, $notification->status);
        $this->assertNull($notification->next_retry_at);
    }

    public function testValidatePhoneNumber(): void
    {
        $validPhones = [
            '+79991234567',
            '+79001234567',
            '+71234567890',
        ];

        foreach ($validPhones as $phone) {
            $isValid = $this->service->validatePhone($phone);
            $this->assertTrue($isValid, "Phone {$phone} should be valid");
        }

        $invalidPhones = [
            '89991234567',
            '79991234567',
            '+7999123456',
            'invalid',
            '',
        ];

        foreach ($invalidPhones as $phone) {
            $isValid = $this->service->validatePhone($phone);
            $this->assertFalse($isValid, "Phone {$phone} should be invalid");
        }
    }

    public function testSendBatchNotifications(): void
    {
        $notifications = [
            new NotificationQueue([
                'phone' => '+79991234567',
                'message' => 'Message 1',
                'status' => NotificationQueue::STATUS_PENDING,
                'max_retries' => 3,
                'retry_count' => 0,
            ]),
            new NotificationQueue([
                'phone' => '+79991234568',
                'message' => 'Message 2',
                'status' => NotificationQueue::STATUS_PENDING,
                'max_retries' => 3,
                'retry_count' => 0,
            ]),
        ];

        $this->mockProvider->expects($this->exactly(2))
            ->method('send')
            ->willReturn(true);

        $results = $this->service->sendBatch($notifications);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]);
        $this->assertTrue($results[1]);
    }

    public function testGetStats(): void
    {
        $stats = $this->service->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('sent', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('failed', $stats);
    }

    public function testLoggingOnError(): void
    {
        $notification = new NotificationQueue([
            'phone' => '+79991234567',
            'message' => 'Test message',
            'status' => NotificationQueue::STATUS_PENDING,
            'max_retries' => 3,
            'retry_count' => 0,
        ]);

        $this->mockProvider->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception('Logged error'));

        $this->service->sendNotification($notification);

        $this->assertStringContainsString('Logged error', $notification->error_message);
    }
}
