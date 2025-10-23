<?php

declare(strict_types=1);

namespace app\common\dto;

/**
 * DTO for notification queue processing result
 */
final readonly class NotificationProcessingResultDto
{
    /**
     * @param int $totalProcessed Total number of notifications processed
     * @param int $successCount Number of successfully sent notifications
     * @param int $failedCount Number of failed notifications
     * @param array $errors Array of error messages ['notification_id' => 'error message']
     */
    public function __construct(
        public int $totalProcessed,
        public int $successCount,
        public int $failedCount,
        public array $errors = [],
    ) {
    }

    /**
     * Check if processing was successful for all notifications
     */
    public function isFullySuccessful(): bool
    {
        return $this->failedCount === 0;
    }

    /**
     * Check if any notifications were processed
     */
    public function hasProcessed(): bool
    {
        return $this->totalProcessed > 0;
    }

    /**
     * Get success rate as percentage
     */
    public function getSuccessRate(): float
    {
        if ($this->totalProcessed === 0) {
            return 0.0;
        }

        return ($this->successCount / $this->totalProcessed) * 100;
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'total_processed' => $this->totalProcessed,
            'success_count' => $this->successCount,
            'failed_count' => $this->failedCount,
            'success_rate' => $this->getSuccessRate(),
            'errors' => $this->errors,
        ];
    }
}
