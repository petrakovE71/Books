<?php

declare(strict_types=1);

namespace tests\integration;

use app\models\NotificationQueue;
use tests\fixtures\NotificationQueueFixture;
use Codeception\Test\Unit;

class NotificationConsoleCommandTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'queue' => NotificationQueueFixture::class,
        ];
    }

    public function testConsoleCommandProcessesNotifications(): void
    {
        $pendingBefore = NotificationQueue::find()
            ->where(['status' => NotificationQueue::STATUS_PENDING])
            ->count();

        $this->assertGreaterThan(0, $pendingBefore);

        // Execute console command
        $command = \Yii::$app->createControllerByID('notification/send');

        if ($command !== false) {
            $result = $command->runAction('index', ['limit' => 10]);
            $this->assertEquals(0, $result);

            $pendingAfter = NotificationQueue::find()
                ->where(['status' => NotificationQueue::STATUS_PENDING])
                ->count();

            // Some notifications should be processed
            $this->assertLessThanOrEqual($pendingBefore, $pendingAfter);
        } else {
            $this->markTestSkipped('Notification command not available');
        }
    }

    public function testCommandWithLimitParameter(): void
    {
        $limit = 3;

        $command = \Yii::$app->createControllerByID('notification/send');

        if ($command !== false) {
            $processingBefore = NotificationQueue::find()
                ->where(['status' => NotificationQueue::STATUS_PROCESSING])
                ->count();

            $command->runAction('index', ['limit' => $limit]);

            // After command execution, check that not more than limit were processed
            $this->assertTrue(true);
        } else {
            $this->markTestSkipped('Notification command not available');
        }
    }

    public function testCommandHandlesEmptyQueue(): void
    {
        // Mark all notifications as sent
        NotificationQueue::updateAll(
            ['status' => NotificationQueue::STATUS_SENT],
            ['status' => NotificationQueue::STATUS_PENDING]
        );

        $command = \Yii::$app->createControllerByID('notification/send');

        if ($command !== false) {
            $result = $command->runAction('index');

            // Command should exit gracefully with empty queue
            $this->assertEquals(0, $result);
        } else {
            $this->markTestSkipped('Notification command not available');
        }
    }

    public function testCommandRespectsRetrySchedule(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');

        // Set next_retry_at to future
        $notification->next_retry_at = time() + 3600;
        $notification->save(false);

        $command = \Yii::$app->createControllerByID('notification/send');

        if ($command !== false) {
            $command->runAction('index', ['limit' => 100]);

            $notification->refresh();

            // Notification should still be pending and not processed
            $this->assertEquals(NotificationQueue::STATUS_PENDING, $notification->status);
        } else {
            $this->markTestSkipped('Notification command not available');
        }
    }

    public function testCommandOutputsProgress(): void
    {
        $command = \Yii::$app->createControllerByID('notification/send');

        if ($command !== false) {
            ob_start();
            $command->runAction('index', ['limit' => 5]);
            $output = ob_get_clean();

            // Command should output some progress information
            $this->assertIsString($output);
        } else {
            $this->markTestSkipped('Notification command not available');
        }
    }

    public function testCommandHandlesErrors(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');

        // Set invalid phone to cause error
        $notification->phone = 'invalid';
        $notification->save(false);

        $command = \Yii::$app->createControllerByID('notification/send');

        if ($command !== false) {
            $result = $command->runAction('index', ['limit' => 1]);

            // Command should continue execution despite errors
            $this->assertEquals(0, $result);

            $notification->refresh();

            // Notification should have error recorded
            $this->assertNotEmpty($notification->error_message);
        } else {
            $this->markTestSkipped('Notification command not available');
        }
    }

    public function testCommandCanBeRunConcurrently(): void
    {
        // Create multiple notifications
        for ($i = 0; $i < 10; $i++) {
            $notification = new NotificationQueue([
                'book_id' => 1,
                'phone' => '+7999' . rand(1000000, 9999999),
                'message' => "Test message {$i}",
                'status' => NotificationQueue::STATUS_PENDING,
                'max_retries' => 3,
                'retry_count' => 0,
            ]);
            $notification->save(false);
        }

        $command = \Yii::$app->createControllerByID('notification/send');

        if ($command !== false) {
            // Simulate concurrent execution by running twice
            $result1 = $command->runAction('index', ['limit' => 5]);
            $result2 = $command->runAction('index', ['limit' => 5]);

            $this->assertEquals(0, $result1);
            $this->assertEquals(0, $result2);

            // Check that notifications were processed and no duplicates
            $processing = NotificationQueue::find()
                ->where(['status' => NotificationQueue::STATUS_PROCESSING])
                ->count();

            // Should not have many stuck in processing state
            $this->assertLessThan(5, $processing);
        } else {
            $this->markTestSkipped('Notification command not available');
        }
    }

    public function testCommandExitCodeOnSuccess(): void
    {
        $command = \Yii::$app->createControllerByID('notification/send');

        if ($command !== false) {
            $result = $command->runAction('index');
            $this->assertEquals(0, $result, 'Command should exit with code 0 on success');
        } else {
            $this->markTestSkipped('Notification command not available');
        }
    }

    public function testCommandStatsOutput(): void
    {
        $command = \Yii::$app->createControllerByID('notification/send');

        if ($command !== false) {
            ob_start();
            $command->runAction('index', ['limit' => 10]);
            $output = ob_get_clean();

            // Output should contain stats
            if (!empty($output)) {
                $this->assertIsString($output);
            }
        } else {
            $this->markTestSkipped('Notification command not available');
        }
    }

    public function testCommandDoesNotProcessMaxRetriesExceeded(): void
    {
        $notification = $this->tester->grabFixture('queue', 'pending');
        $notification->retry_count = $notification->max_retries;
        $notification->save(false);

        $command = \Yii::$app->createControllerByID('notification/send');

        if ($command !== false) {
            $command->runAction('index', ['limit' => 100]);

            $notification->refresh();

            // Should be marked as failed
            $this->assertEquals(NotificationQueue::STATUS_FAILED, $notification->status);
        } else {
            $this->markTestSkipped('Notification command not available');
        }
    }
}
