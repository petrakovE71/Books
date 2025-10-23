<?php

declare(strict_types=1);

namespace tests\unit\services;

use app\common\services\NotificationProcessingService;
use app\components\sms\SmsService;
use app\common\dto\NotificationProcessingResultDto;
use app\common\exceptions\SmsDeliveryException;
use app\models\NotificationQueue;
use tests\fixtures\NotificationQueueFixture;
use Codeception\Test\Unit;

class NotificationProcessingServiceTest extends Unit
{
    private NotificationProcessingService $service;
    private $mockSmsService;

    public function _fixtures(): array
    {
        return [
            'queue' => NotificationQueueFixture::class,
        ];
    }

    protected function _before(): void
    {
        parent::_before();

        $this->mockSmsService = $this->createMock(SmsService::class);
        $this->service = new NotificationProcessingService($this->mockSmsService);
    }

    public function testProcessQueueThrowsExceptionWhenServiceUnavailable(): void
    {
        $this->mockSmsService->expects($this->once())
            ->method('isAvailable')
            ->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SMS service is unavailable');

        $this->service->processQueue(10);
    }

    public function testProcessQueueReturnsEmptyResultWhenNoNotifications(): void
    {
        // Mark all as sent
        NotificationQueue::updateAll(['status' => NotificationQueue::STATUS_SENT]);

        $this->mockSmsService->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $result = $this->service->processQueue(10);

        $this->assertInstanceOf(NotificationProcessingResultDto::class, $result);
        $this->assertEquals(0, $result->totalProcessed);
        $this->assertEquals(0, $result->successCount);
        $this->assertEquals(0, $result->failedCount);
        $this->assertFalse($result->hasProcessed());
    }

    public function testProcessQueueSuccessfully(): void
    {
        $this->mockSmsService->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $this->mockSmsService->expects($this->atLeastOnce())
            ->method('send')
            ->willReturn(true);

        $result = $this->service->processQueue(5);

        $this->assertInstanceOf(NotificationProcessingResultDto::class, $result);
        $this->assertGreaterThan(0, $result->totalProcessed);
        $this->assertGreaterThan(0, $result->successCount);
        $this->assertEquals(0, $result->failedCount);
        $this->assertTrue($result->isFullySuccessful());
    }

    public function testProcessQueueHandlesSmsDeliveryException(): void
    {
        $this->mockSmsService->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $this->mockSmsService->expects($this->atLeastOnce())
            ->method('send')
            ->willThrowException(new SmsDeliveryException('SMS delivery failed'));

        $result = $this->service->processQueue(1);

        $this->assertGreaterThan(0, $result->failedCount);
        $this->assertNotEmpty($result->errors);
        $this->assertFalse($result->isFullySuccessful());
    }

    public function testProcessQueueHandlesCriticalException(): void
    {
        $this->mockSmsService->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $this->mockSmsService->expects($this->atLeastOnce())
            ->method('send')
            ->willThrowException(new \RuntimeException('Critical error'));

        $result = $this->service->processQueue(1);

        $this->assertGreaterThan(0, $result->failedCount);
        $this->assertNotEmpty($result->errors);
    }

    public function testProcessQueueRespectsLimit(): void
    {
        $limit = 2;

        $this->mockSmsService->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $this->mockSmsService->expects($this->exactly($limit))
            ->method('send')
            ->willReturn(true);

        $result = $this->service->processQueue($limit);

        $this->assertLessThanOrEqual($limit, $result->totalProcessed);
    }

    public function testGetStatistics(): void
    {
        $stats = $this->service->getStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('processing', $stats);
        $this->assertArrayHasKey('sent', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('total', $stats);

        $this->assertEquals(
            $stats['pending'] + $stats['processing'] + $stats['sent'] + $stats['failed'],
            $stats['total']
        );
    }

    public function testCleanupOldNotifications(): void
    {
        // Create old sent notification
        $oldNotification = new NotificationQueue([
            'book_id' => 1,
            'phone' => '+79991234567',
            'message' => 'Old notification',
            'status' => NotificationQueue::STATUS_SENT,
            'sent_at' => time() - (40 * 24 * 60 * 60), // 40 days ago
            'created_at' => time() - (40 * 24 * 60 * 60),
        ]);
        $oldNotification->save(false);

        $countBefore = NotificationQueue::find()
            ->where(['status' => NotificationQueue::STATUS_SENT])
            ->count();

        $deletedCount = $this->service->cleanupOldNotifications(30);

        $countAfter = NotificationQueue::find()
            ->where(['status' => NotificationQueue::STATUS_SENT])
            ->count();

        $this->assertGreaterThan(0, $deletedCount);
        $this->assertLessThan($countBefore, $countAfter);
    }

    public function testCleanupDoesNotDeleteRecentNotifications(): void
    {
        // Create recent sent notification
        $recentNotification = new NotificationQueue([
            'book_id' => 1,
            'phone' => '+79991234567',
            'message' => 'Recent notification',
            'status' => NotificationQueue::STATUS_SENT,
            'sent_at' => time() - (10 * 24 * 60 * 60), // 10 days ago
            'created_at' => time() - (10 * 24 * 60 * 60),
        ]);
        $recentNotification->save(false);

        $deletedCount = $this->service->cleanupOldNotifications(30);

        // Recent notification should still exist
        $notification = NotificationQueue::findOne($recentNotification->id);
        $this->assertNotNull($notification);
    }

    public function testCleanupThrowsExceptionForInvalidDays(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Days old must be at least 1');

        $this->service->cleanupOldNotifications(0);
    }

    public function testCleanupWithCustomDays(): void
    {
        $customDays = 60;

        $deletedCount = $this->service->cleanupOldNotifications($customDays);

        $this->assertIsInt($deletedCount);
        $this->assertGreaterThanOrEqual(0, $deletedCount);
    }

    public function testIsServiceAvailable(): void
    {
        $this->mockSmsService->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $result = $this->service->isServiceAvailable();

        $this->assertTrue($result);
    }

    public function testGetProviderName(): void
    {
        $this->mockSmsService->expects($this->once())
            ->method('getProviderName')
            ->willReturn('SMS Pilot');

        $result = $this->service->getProviderName();

        $this->assertEquals('SMS Pilot', $result);
    }

    public function testProcessQueueMarksNotificationAsProcessing(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');
        $originalStatus = $notification->status;

        $this->mockSmsService->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        $this->mockSmsService->expects($this->atLeastOnce())
            ->method('send')
            ->willReturn(true);

        $this->service->processQueue(1);

        $notification->refresh();

        // Should be marked as sent now
        $this->assertEquals(NotificationQueue::STATUS_SENT, $notification->status);
    }

    public function testResultDtoCalculatesSuccessRate(): void
    {
        $this->mockSmsService->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);

        // Mix of success and failure
        $callCount = 0;
        $this->mockSmsService->expects($this->atLeastOnce())
            ->method('send')
            ->willReturnCallback(function() use (&$callCount) {
                $callCount++;
                return $callCount % 2 === 0; // Every second call succeeds
            });

        $result = $this->service->processQueue(4);

        $successRate = $result->getSuccessRate();

        $this->assertIsFloat($successRate);
        $this->assertGreaterThanOrEqual(0.0, $successRate);
        $this->assertLessThanOrEqual(100.0, $successRate);
    }
}
