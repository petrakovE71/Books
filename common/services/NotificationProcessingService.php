<?php

declare(strict_types=1);

namespace app\common\services;

use Yii;
use app\models\NotificationQueue;
use app\components\sms\SmsService;
use app\common\dto\NotificationProcessingResultDto;
use app\common\exceptions\SmsDeliveryException;

/**
 * Service for processing notification queue
 */
final class NotificationProcessingService
{
    private const RATE_LIMIT_DELAY_MICROSECONDS = 100000; // 0.1 second

    public function __construct(
        private readonly SmsService $smsService,
    ) {
    }

    /**
     * Process notification queue
     *
     * @param int $limit Maximum number of notifications to process
     * @return NotificationProcessingResultDto Processing result
     * @throws \Exception If SMS service is unavailable
     */
    public function processQueue(int $limit): NotificationProcessingResultDto
    {
        if (!$this->smsService->isAvailable()) {
            throw new \RuntimeException('SMS service is unavailable');
        }

        $notifications = NotificationQueue::findReadyForSending($limit)->all();

        if (empty($notifications)) {
            return new NotificationProcessingResultDto(
                totalProcessed: 0,
                successCount: 0,
                failedCount: 0,
            );
        }

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($notifications as $notification) {
            /** @var NotificationQueue $notification */

            try {
                $this->processSingleNotification($notification);
                $successCount++;
            } catch (SmsDeliveryException $e) {
                $failedCount++;
                $errors[$notification->id] = $e->getMessage();

                Yii::error(
                    "Failed to send notification #{$notification->id}: {$e->getMessage()}",
                    __METHOD__
                );
            } catch (\Throwable $e) {
                $failedCount++;
                $errors[$notification->id] = $e->getMessage();

                $notification->markAsPermanentlyFailed($e->getMessage());

                Yii::error(
                    "Critical error sending notification #{$notification->id}: {$e->getMessage()}",
                    __METHOD__
                );
            }

            // Rate limiting at application level
            usleep(self::RATE_LIMIT_DELAY_MICROSECONDS);
        }

        return new NotificationProcessingResultDto(
            totalProcessed: count($notifications),
            successCount: $successCount,
            failedCount: $failedCount,
            errors: $errors,
        );
    }

    /**
     * Process a single notification
     *
     * @param NotificationQueue $notification
     * @return void
     * @throws SmsDeliveryException
     */
    private function processSingleNotification(NotificationQueue $notification): void
    {
        $notification->markAsProcessing();

        // Send SMS with retry on queue level = 1 (service has its own retry)
        $result = $this->smsService->send(
            phone: $notification->phone,
            message: $notification->message,
            maxRetries: 1
        );

        if ($result) {
            $notification->markAsSent();
        } else {
            $notification->markAsFailedWithRetry('Failed to send SMS');
            throw new SmsDeliveryException('Failed to send SMS');
        }
    }

    /**
     * Get notification queue statistics
     *
     * @return array Statistics by status
     */
    public function getStatistics(): array
    {
        $stats = [
            'pending' => NotificationQueue::find()
                ->where(['status' => NotificationQueue::STATUS_PENDING])
                ->count(),
            'processing' => NotificationQueue::find()
                ->where(['status' => NotificationQueue::STATUS_PROCESSING])
                ->count(),
            'sent' => NotificationQueue::find()
                ->where(['status' => NotificationQueue::STATUS_SENT])
                ->count(),
            'failed' => NotificationQueue::find()
                ->where(['status' => NotificationQueue::STATUS_FAILED])
                ->count(),
        ];

        $stats['total'] = array_sum($stats);

        return $stats;
    }

    /**
     * Cleanup old sent notifications
     *
     * @param int $daysOld Number of days to keep (default: 30)
     * @return int Number of deleted records
     */
    public function cleanupOldNotifications(int $daysOld = 30): int
    {
        if ($daysOld < 1) {
            throw new \InvalidArgumentException('Days old must be at least 1');
        }

        $timestamp = time() - ($daysOld * 24 * 60 * 60);

        return NotificationQueue::deleteAll([
            'and',
            ['status' => NotificationQueue::STATUS_SENT],
            ['<', 'sent_at', $timestamp]
        ]);
    }

    /**
     * Check if SMS service is available
     *
     * @return bool
     */
    public function isServiceAvailable(): bool
    {
        return $this->smsService->isAvailable();
    }

    /**
     * Get SMS service provider name
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->smsService->getProviderName();
    }
}
