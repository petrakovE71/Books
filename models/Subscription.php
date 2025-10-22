<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use yii\behaviors\TimestampBehavior;

/**
 * @property int $id
 * @property int $author_id
 * @property string $name
 * @property string $phone
 * @property int $created_at
 *
 * @property Author $author
 * @property NotificationQueue[] $notificationQueue
 */
class Subscription extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%subscription}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false, // Subscriptions are immutable
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['author_id', 'name', 'phone'], 'required'],
            [['author_id'], 'integer'],
            [['author_id'], 'exist', 'skipOnError' => true,
             'targetClass' => Author::class,
             'targetAttribute' => ['author_id' => 'id']],

            [['name'], 'string', 'max' => 255],
            [['name'], 'trim'],
            [['name'], 'match', 'pattern' => '/^[А-ЯЁа-яёA-Za-z\s\-]+$/u',
             'message' => 'Name must contain only letters, spaces and hyphens'],

            [['phone'], 'string', 'max' => 20],
            [['phone'], 'trim'],
            [['phone'], 'match', 'pattern' => '/^\+?[1-9]\d{1,14}$/',
             'message' => 'Phone must be in international format, e.g.: +79991234567'],

            [['phone'], 'unique', 'targetAttribute' => ['phone', 'author_id'],
             'message' => 'You are already subscribed to this author'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'author_id' => 'Author',
            'name' => 'Name',
            'phone' => 'Phone',
            'created_at' => 'Created At',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getAuthor(): ActiveQuery
    {
        return $this->hasOne(Author::class, ['id' => 'author_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getNotificationQueue(): ActiveQuery
    {
        return $this->hasMany(NotificationQueue::class, ['subscription_id' => 'id']);
    }

    /**
     * @return string
     */
    public function getFormattedPhone(): string
    {
        if (preg_match('/^\+?7(\d{3})(\d{3})(\d{2})(\d{2})$/', $this->phone, $matches)) {
            return "+7 ({$matches[1]}) {$matches[2]}-{$matches[3]}-{$matches[4]}";
        }
        return $this->phone;
    }

    /**
     * Normalize phone before save
     *
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            // Remove all non-digit characters except leading +
            $this->phone = preg_replace('/[^\d+]/', '', $this->phone);

            // Ensure + at the beginning for international format
            if ($this->phone[0] !== '+') {
                // If starts with 8 (Russian format), replace with +7
                if ($this->phone[0] === '8') {
                    $this->phone = '+7' . substr($this->phone, 1);
                } else {
                    $this->phone = '+' . $this->phone;
                }
            }

            return true;
        }
        return false;
    }
}
