<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%notification_queue}}`.
 */
class m241022_150500_create_notification_queue_table extends Migration
{
    /**
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%notification_queue}}', [
            'id' => $this->primaryKey(),
            'subscription_id' => $this->integer()->notNull()->comment('ID подписки'),
            'book_id' => $this->integer()->notNull()->comment('ID книги, о которой уведомляем'),
            'phone' => $this->string(20)->notNull()->comment('Телефон получателя (денормализация для скорости)'),
            'message' => $this->text()->notNull()->comment('Текст SMS сообщения'),
            'status' => $this->string(20)->notNull()->defaultValue(self::STATUS_PENDING)->comment('Статус: pending, processing, sent, failed'),
            'retry_count' => $this->integer()->notNull()->defaultValue(0)->comment('Количество попыток отправки'),
            'max_retries' => $this->integer()->notNull()->defaultValue(3)->comment('Максимальное количество попыток'),
            'error_message' => $this->text()->null()->comment('Текст ошибки при неудачной отправке'),
            'sent_at' => $this->integer()->null()->comment('Дата успешной отправки'),
            'next_retry_at' => $this->integer()->null()->comment('Дата следующей попытки отправки'),
            'created_at' => $this->integer()->notNull()->comment('Дата создания записи'),
            'updated_at' => $this->integer()->notNull()->comment('Дата обновления записи'),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->createIndex(
            'idx-notification_queue-status-next_retry',
            '{{%notification_queue}}',
            ['status', 'next_retry_at']
        );

        $this->createIndex(
            'idx-notification_queue-subscription_id',
            '{{%notification_queue}}',
            'subscription_id'
        );

        $this->createIndex(
            'idx-notification_queue-book_id',
            '{{%notification_queue}}',
            'book_id'
        );

        $this->createIndex(
            'idx-notification_queue-unique',
            '{{%notification_queue}}',
            ['subscription_id', 'book_id'],
            true
        );

        $this->addForeignKey(
            'fk-notification_queue-subscription_id',
            '{{%notification_queue}}',
            'subscription_id',
            '{{%subscription}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-notification_queue-book_id',
            '{{%notification_queue}}',
            'book_id',
            '{{%book}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-notification_queue-subscription_id', '{{%notification_queue}}');
        $this->dropForeignKey('fk-notification_queue-book_id', '{{%notification_queue}}');
        $this->dropTable('{{%notification_queue}}');
    }
}
