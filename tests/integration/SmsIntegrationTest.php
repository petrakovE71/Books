<?php

declare(strict_types=1);

namespace tests\integration;

use app\common\services\SmsService;
use app\common\sms\SmsPilotProvider;
use app\models\NotificationQueue;
use tests\fixtures\NotificationQueueFixture;
use Codeception\Test\Unit;

class SmsIntegrationTest extends Unit
{
    private SmsService $smsService;

    public function _fixtures(): array
    {
        return [
            'queue' => NotificationQueueFixture::class,
        ];
    }

    protected function _before(): void
    {
        parent::_before();

        // Use mock provider for testing
        $mockProvider = $this->createMock(SmsPilotProvider::class);
        $this->smsService = new SmsService($mockProvider);
    }

    public function testSendSmsSuccess(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');

        $mockProvider = $this->createMock(SmsPilotProvider::class);
        $mockProvider->expects($this->once())
            ->method('send')
            ->with($notification->phone, $notification->message)
            ->willReturn(true);

        $smsService = new SmsService($mockProvider);
        $result = $smsService->sendNotification($notification);

        $this->assertTrue($result);
        $this->assertEquals(NotificationQueue::STATUS_SENT, $notification->status);
        $this->assertNotNull($notification->sent_at);
    }

    public function testSendSmsFailure(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');

        $mockProvider = $this->createMock(SmsPilotProvider::class);
        $mockProvider->expects($this->once())
            ->method('send')
            ->with($notification->phone, $notification->message)
            ->willThrowException(new \Exception('SMS provider error'));

        $smsService = new SmsService($mockProvider);
        $result = $smsService->sendNotification($notification);

        $this->assertFalse($result);
        $this->assertEquals(NotificationQueue::STATUS_PENDING, $notification->status);
        $this->assertNotNull($notification->next_retry_at);
        $this->assertStringContainsString('SMS provider error', $notification->error_message);
    }

    public function testBatchSmsSending(): void
    {
        $notifications = NotificationQueue::findReadyForSending(5)->all();

        $mockProvider = $this->createMock(SmsPilotProvider::class);
        $mockProvider->expects($this->exactly(count($notifications)))
            ->method('send')
            ->willReturn(true);

        $smsService = new SmsService($mockProvider);

        $successCount = 0;
        foreach ($notifications as $notification) {
            if ($smsService->sendNotification($notification)) {
                $successCount++;
            }
        }

        $this->assertEquals(count($notifications), $successCount);
    }

    public function testSmsRetryAfterTemporaryFailure(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');
        $notification->retry_count = 0;
        $notification->save(false);

        $mockProvider = $this->createMock(SmsPilotProvider::class);

        // First attempt fails
        $mockProvider->expects($this->at(0))
            ->method('send')
            ->willThrowException(new \Exception('Temporary failure'));

        $smsService = new SmsService($mockProvider);
        $result = $smsService->sendNotification($notification);

        $this->assertFalse($result);
        $this->assertEquals(NotificationQueue::STATUS_PENDING, $notification->status);
        $this->assertGreaterThan(0, $notification->retry_count);

        // Simulate second attempt succeeds
        $mockProvider2 = $this->createMock(SmsPilotProvider::class);
        $mockProvider2->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $smsService2 = new SmsService($mockProvider2);

        // Set next_retry_at to past to make it ready for sending
        $notification->next_retry_at = time() - 1;
        $notification->save(false);

        $result2 = $smsService2->sendNotification($notification);

        $this->assertTrue($result2);
        $this->assertEquals(NotificationQueue::STATUS_SENT, $notification->status);
    }

    public function testSmsProviderConnectionError(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');

        $mockProvider = $this->createMock(SmsPilotProvider::class);
        $mockProvider->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception('Connection timeout'));

        $smsService = new SmsService($mockProvider);
        $result = $smsService->sendNotification($notification);

        $this->assertFalse($result);
        $this->assertStringContainsString('Connection timeout', $notification->error_message);
    }

    public function testSmsMaxRetriesExhausted(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');
        $notification->retry_count = $notification->max_retries - 1;
        $notification->save(false);

        $mockProvider = $this->createMock(SmsPilotProvider::class);
        $mockProvider->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception('Permanent failure'));

        $smsService = new SmsService($mockProvider);
        $result = $smsService->sendNotification($notification);

        $this->assertFalse($result);
        $this->assertEquals(NotificationQueue::STATUS_FAILED, $notification->status);
        $this->assertNull($notification->next_retry_at);
        $this->assertFalse($notification->canRetry());
    }

    public function testSmsPhoneNumberValidation(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');
        $notification->phone = 'invalid-phone';
        $notification->save(false);

        $mockProvider = $this->createMock(SmsPilotProvider::class);
        $mockProvider->expects($this->never())
            ->method('send');

        $smsService = new SmsService($mockProvider);
        $result = $smsService->sendNotification($notification);

        $this->assertFalse($result);
        $this->assertStringContainsString('Invalid phone', $notification->error_message);
    }

    public function testSmsRateLimiting(): void
    {
        $this->markTestSkipped('Rate limiting implementation depends on provider configuration');
    }

    public function testSmsLoggingOnFailure(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');

        $mockProvider = $this->createMock(SmsPilotProvider::class);
        $mockProvider->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception('Test error for logging'));

        $smsService = new SmsService($mockProvider);
        $smsService->sendNotification($notification);

        $this->assertNotEmpty($notification->error_message);
        $this->assertStringContainsString('Test error for logging', $notification->error_message);
    }
}
