<?php

declare(strict_types=1);

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\common\services\NotificationProcessingService;

/**
 * NotificationController
 * Console controller for notification queue management
 */
class NotificationController extends Controller
{
    /**
     * Maximum number of notifications to process per run
     */
    public int $limit = 100;

    /**
     * {@inheritdoc}
     */
    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['limit']);
    }

    /**
     * {@inheritdoc}
     */
    public function optionAliases(): array
    {
        return array_merge(parent::optionAliases(), [
            'l' => 'limit',
        ]);
    }

    /**
     * Send SMS notifications from queue
     *
     * @return int Exit code
     */
    public function actionSend(): int
    {
        $this->stdout("Starting notification processing...\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("Limit: {$this->limit} notifications\n");

        /** @var NotificationProcessingService $service */
        $service = Yii::$app->get('notificationProcessingService');

        // Check service availability
        if (!$service->isServiceAvailable()) {
            $this->stderr("SMS service is unavailable!\n", \yii\helpers\Console::FG_RED);
            return ExitCode::UNAVAILABLE;
        }

        $this->stdout("SMS Provider: {$service->getProviderName()}\n");
        $this->stdout("Service status: Available\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("\n");

        try {
            $result = $service->processQueue($this->limit);

            if (!$result->hasProcessed()) {
                $this->stdout("No notifications to send.\n");
                return ExitCode::OK;
            }

            // Output processing results
            $this->outputProcessingResult($result);

            return ExitCode::OK;
        } catch (\RuntimeException $e) {
            $this->stderr("Error: {$e->getMessage()}\n", \yii\helpers\Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /**
     * Show notification queue statistics
     *
     * @return int Exit code
     */
    public function actionStats(): int
    {
        $this->stdout("Notification Queue Statistics\n", \yii\helpers\Console::FG_CYAN);
        $this->stdout("=====================================\n");

        /** @var NotificationProcessingService $service */
        $service = Yii::$app->get('notificationProcessingService');

        $stats = $service->getStatistics();

        // Output statistics
        $statusColors = [
            'pending' => \yii\helpers\Console::FG_YELLOW,
            'processing' => \yii\helpers\Console::FG_CYAN,
            'sent' => \yii\helpers\Console::FG_GREEN,
            'failed' => \yii\helpers\Console::FG_RED,
        ];

        foreach ($stats as $status => $count) {
            if ($status === 'total') {
                continue;
            }

            $label = ucfirst($status);
            $color = $statusColors[$status] ?? \yii\helpers\Console::RESET;

            $this->stdout(str_pad($label . ':', 15), $color);
            $this->stdout("{$count}\n");
        }

        $this->stdout("=====================================\n");
        $this->stdout("Total: {$stats['total']}\n", \yii\helpers\Console::FG_CYAN);

        return ExitCode::OK;
    }

    /**
     * Cleanup old sent notifications (older than specified days)
     *
     * @param int $days Number of days to keep (default: 30)
     * @return int Exit code
     */
    public function actionCleanup(int $days = 30): int
    {
        $this->stdout("Cleaning up old notifications (older than {$days} days)...\n", \yii\helpers\Console::FG_CYAN);

        /** @var NotificationProcessingService $service */
        $service = Yii::$app->get('notificationProcessingService');

        try {
            $count = $service->cleanupOldNotifications($days);

            $this->stdout("Deleted {$count} old sent notifications.\n", \yii\helpers\Console::FG_GREEN);

            return ExitCode::OK;
        } catch (\InvalidArgumentException $e) {
            $this->stderr("Error: {$e->getMessage()}\n", \yii\helpers\Console::FG_RED);
            return ExitCode::DATAERR;
        }
    }

    /**
     * Output processing result in formatted way
     *
     * @param \app\common\dto\NotificationProcessingResultDto $result
     * @return void
     */
    private function outputProcessingResult(\app\common\dto\NotificationProcessingResultDto $result): void
    {
        $this->stdout("Found {$result->totalProcessed} notifications ready for sending.\n\n", \yii\helpers\Console::FG_CYAN);

        // Output errors if any
        if (!empty($result->errors)) {
            foreach ($result->errors as $notificationId => $error) {
                $this->stderr("Notification #{$notificationId}: {$error}\n", \yii\helpers\Console::FG_RED);
            }
            $this->stdout("\n");
        }

        // Output summary
        $this->stdout("=====================================\n");
        $this->stdout("Processing completed!\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("Total processed: {$result->totalProcessed}\n");
        $this->stdout("Successfully sent: {$result->successCount}\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout(
            "Failed: {$result->failedCount}\n",
            $result->failedCount > 0 ? \yii\helpers\Console::FG_RED : \yii\helpers\Console::FG_GREEN
        );
        $this->stdout(sprintf("Success rate: %.2f%%\n", $result->getSuccessRate()));
        $this->stdout("=====================================\n");
    }
}
