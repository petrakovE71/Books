<?php

declare(strict_types=1);

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\NotificationQueue;
use app\components\sms\SmsService;
use app\common\exceptions\SmsDeliveryException;

/**
 * NotificationController
 *
 */
class NotificationController extends Controller
{
    /**
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
     * Отправить SMS уведомления из очереди
     *
     * @return int Exit code
     */
    public function actionSend(): int
    {
        $this->stdout("Starting notification processing...\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("Limit: {$this->limit} notifications\n");

        /** @var SmsService $smsService */
        $smsService = Yii::$app->get('smsService');

       
        if (!$smsService->isAvailable()) {
            $this->stderr("SMS service is unavailable!\n", \yii\helpers\Console::FG_RED);
            return ExitCode::UNAVAILABLE;
        }

        $this->stdout("SMS Provider: {$smsService->getProviderName()}\n");
        $this->stdout("Service status: Available\n", \yii\helpers\Console::FG_GREEN);

       
        $notifications = NotificationQueue::findReadyForSending($this->limit)->all();

        if (empty($notifications)) {
            $this->stdout("No notifications to send.\n");
            return ExitCode::OK;
        }

        $totalCount = count($notifications);
        $this->stdout("Found {$totalCount} notifications ready for sending.\n\n", \yii\helpers\Console::FG_CYAN);

        $successCount = 0;
        $failedCount = 0;

        foreach ($notifications as $i => $notification) {
            /** @var NotificationQueue $notification */

            $num = $i + 1;
            $this->stdout("[{$num}/{$totalCount}] Sending to {$notification->phone}... ");

            try {
               
                $notification->markAsProcessing();

                // Отправляем SMS
                $result = $smsService->send(
                    phone: $notification->phone,
                    message: $notification->message,
                    maxRetries: 1 // В console уже есть retry на уровне очереди
                );

                if ($result) {
                    // Успешно отправлено
                    $notification->markAsSent();
                    $this->stdout("OK\n", \yii\helpers\Console::FG_GREEN);
                    $successCount++;
                } else {
                    // Неудачная отправка - возвращаем в pending для retry
                    $notification->markAsFailedWithRetry('Failed to send SMS');
                    $this->stdout("FAILED (will retry)\n", \yii\helpers\Console::FG_YELLOW);
                    $failedCount++;
                }
            } catch (SmsDeliveryException $e) {
                // Ошибка отправки
                $notification->markAsFailedWithRetry($e->getMessage());
                $this->stderr("ERROR: {$e->getMessage()}\n", \yii\helpers\Console::FG_RED);
                $failedCount++;

                // Логируем ошибку
                Yii::error(
                    "Failed to send notification #{$notification->id}: {$e->getMessage()}",
                    __METHOD__
                );
            } catch (\Throwable $e) {
                // Критическая ошибка - помечаем как permanently failed
                $notification->markAsPermanentlyFailed($e->getMessage());
                $this->stderr("CRITICAL ERROR: {$e->getMessage()}\n", \yii\helpers\Console::FG_RED);
                $failedCount++;

                // Логируем ошибку
                Yii::error(
                    "Critical error sending notification #{$notification->id}: {$e->getMessage()}",
                    __METHOD__
                );
            }

            // Небольшая задержка между отправками (rate limiting на уровне приложения)
            usleep(100000); // 0.1 second
        }

        $this->stdout("\n");
        $this->stdout("=====================================\n");
        $this->stdout("Processing completed!\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("Total processed: {$totalCount}\n");
        $this->stdout("Successfully sent: {$successCount}\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("Failed: {$failedCount}\n", $failedCount > 0 ? \yii\helpers\Console::FG_RED : \yii\helpers\Console::FG_GREEN);
        $this->stdout("=====================================\n");

        return ExitCode::OK;
    }

    /**
     * Показать статистику очереди уведомлений
     *
     * @return int Exit code
     */
    public function actionStats(): int
    {
        $this->stdout("Notification Queue Statistics\n", \yii\helpers\Console::FG_CYAN);
        $this->stdout("=====================================\n");

        $stats = [
            'Pending' => NotificationQueue::find()->where(['status' => NotificationQueue::STATUS_PENDING])->count(),
            'Processing' => NotificationQueue::find()->where(['status' => NotificationQueue::STATUS_PROCESSING])->count(),
            'Sent' => NotificationQueue::find()->where(['status' => NotificationQueue::STATUS_SENT])->count(),
            'Failed' => NotificationQueue::find()->where(['status' => NotificationQueue::STATUS_FAILED])->count(),
        ];

        $total = array_sum($stats);

        foreach ($stats as $status => $count) {
            $color = match($status) {
                'Pending' => \yii\helpers\Console::FG_YELLOW,
                'Processing' => \yii\helpers\Console::FG_CYAN,
                'Sent' => \yii\helpers\Console::FG_GREEN,
                'Failed' => \yii\helpers\Console::FG_RED,
                default => \yii\helpers\Console::RESET,
            };

            $this->stdout(str_pad($status . ':', 15), $color);
            $this->stdout("{$count}\n");
        }

        $this->stdout("=====================================\n");
        $this->stdout("Total: {$total}\n", \yii\helpers\Console::FG_CYAN);

        return ExitCode::OK;
    }

    /**
     * Очистить старые отправленные уведомления (старше 30 дней)
     *
     * @return int Exit code
     */
    public function actionCleanup(): int
    {
        $this->stdout("Cleaning up old notifications...\n", \yii\helpers\Console::FG_CYAN);

        $thirtyDaysAgo = time() - (30 * 24 * 60 * 60);

        $count = NotificationQueue::deleteAll([
            'and',
            ['status' => NotificationQueue::STATUS_SENT],
            ['<', 'sent_at', $thirtyDaysAgo]
        ]);

        $this->stdout("Deleted {$count} old sent notifications.\n", \yii\helpers\Console::FG_GREEN);

        return ExitCode::OK;
    }
}
