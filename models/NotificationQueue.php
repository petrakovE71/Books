<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use yii\behaviors\TimestampBehavior;

/**
 * @property int $id
 * @property int $subscription_id
 * @property int $book_id
 * @property string $phone
 * @property string $message
 * @property string $status
 * @property int $retry_count
 * @property int $max_retries
 * @property string|null $error_message
 * @property int|null $sent_at
 * @property int|null $next_retry_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Subscription $subscription
 * @property Book $book
 */
class NotificationQueue extends ActiveRecord
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%notification_queue}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['subscription_id', 'book_id', 'phone', 'message'], 'required'],
            [['subscription_id', 'book_id', 'retry_count', 'max_retries', 'sent_at', 'next_retry_at'], 'integer'],

            [['subscription_id'], 'exist', 'skipOnError' => true,
             'targetClass' => Subscription::class,
             'targetAttribute' => ['subscription_id' => 'id']],

            [['book_id'], 'exist', 'skipOnError' => true,
             'targetClass' => Book::class,
             'targetAttribute' => ['book_id' => 'id']],

            [['message', 'error_message'], 'string'],
            [['phone'], 'string', 'max' => 20],
            [['status'], 'string', 'max' => 20],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['status'], 'in', 'range' => [
                self::STATUS_PENDING,
                self::STATUS_PROCESSING,
                self::STATUS_SENT,
                self::STATUS_FAILED
            ]],

            [['retry_count'], 'default', 'value' => 0],
            [['max_retries'], 'default', 'value' => 3],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'subscription_id' => 'Subscription',
            'book_id' => 'Book',
            'phone' => 'Phone',
            'message' => 'Message',
            'status' => 'Status',
            'retry_count' => 'Retry Count',
            'max_retries' => 'Max Retries',
            'error_message' => 'Error',
            'sent_at' => 'Sent At',
            'next_retry_at' => 'Next Retry At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getSubscription(): ActiveQuery
    {
        return $this->hasOne(Subscription::class, ['id' => 'subscription_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getBook(): ActiveQuery
    {
        return $this->hasOne(Book::class, ['id' => 'book_id']);
    }

    /**
     * @param int $limit
     * @return ActiveQuery
     */
    public static function findReadyForSending(int $limit = 100): ActiveQuery
    {
        $now = time();

        return static::find()
            ->where(['status' => self::STATUS_PENDING])
            ->andWhere([
                'or',
                ['next_retry_at' => null],
                ['<=', 'next_retry_at', $now]
            ])
            ->andWhere(['<', 'retry_count', new \yii\db\Expression('max_retries')])
            ->orderBy(['created_at' => SORT_ASC])
            ->limit($limit);
    }

    /**
     * @return bool
     */
    public function markAsProcessing(): bool
    {
        $this->status = self::STATUS_PROCESSING;
        $this->retry_count++;
        return $this->save(false);
    }

    /**
     * @return bool
     */
    public function markAsSent(): bool
    {
        $this->status = self::STATUS_SENT;
        $this->sent_at = time();
        $this->error_message = null;
        return $this->save(false);
    }

    /**
     * @param string $errorMessage
     * @return bool
     */
    public function markAsFailedWithRetry(string $errorMessage): bool
    {
        $this->error_message = $errorMessage;

        if ($this->retry_count >= $this->max_retries) {
            $this->status = self::STATUS_FAILED;
            $this->next_retry_at = null;
        } else {
            $this->status = self::STATUS_PENDING;

            $delays = [60, 300, 900];
            $delayIndex = min($this->retry_count - 1, count($delays) - 1);
            $this->next_retry_at = time() + $delays[$delayIndex];
        }

        return $this->save(false);
    }

    /**
     * @param string $errorMessage
     * @return bool
     */
    public function markAsPermanentlyFailed(string $errorMessage): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->error_message = $errorMessage;
        $this->next_retry_at = null;
        return $this->save(false);
    }

    /**
     * @return string
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_SENT => 'Sent',
            self::STATUS_FAILED => 'Failed',
            default => $this->status,
        };
    }

    /**
     * @return bool
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->retry_count < $this->max_retries;
    }
}
