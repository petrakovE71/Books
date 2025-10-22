<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use yii\behaviors\TimestampBehavior;

/**
 * @property int $id
 * @property string $fio
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $deleted_at
 *
 * @property Book[] $books
 * @property Subscription[] $subscriptions
 */
class Author extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%author}}';
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
            [['fio'], 'required'],
            [['fio'], 'string', 'max' => 255],
            [['fio'], 'trim'],

            ['fio', 'match', 'pattern' => '/^[А-ЯЁа-яёA-Za-z\s\-]+$/u',
             'message' => 'FIO must contain only letters, spaces and hyphens'],

            [['deleted_at'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'fio' => 'FIO',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'deleted_at' => 'Deleted At',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getBooks(): ActiveQuery
    {
        return $this->hasMany(Book::class, ['id' => 'book_id'])
            ->viaTable('{{%book_author}}', ['author_id' => 'id'])
            ->where(['book.deleted_at' => null]);
    }

    /**
     * @return ActiveQuery
     */
    public function getSubscriptions(): ActiveQuery
    {
        return $this->hasMany(Subscription::class, ['author_id' => 'id']);
    }

    /**
     * @return int
     */
    public function getBooksCount(): int
    {
        return (int)$this->getBooks()->count();
    }

    /**
     * @param int $year
     * @return int
     */
    public function getBooksCountByYear(int $year): int
    {
        return (int)$this->getBooks()->where(['year' => $year])->count();
    }

    /**
     * @return ActiveQuery
     */
    public static function find(): ActiveQuery
    {
        return parent::find()->where(['deleted_at' => null]);
    }

    /**
     * @return bool
     */
    public function softDelete(): bool
    {
        $this->deleted_at = time();
        return $this->save(false);
    }

    /**
     * @return bool
     */
    public function restore(): bool
    {
        $this->deleted_at = null;
        return $this->save(false);
    }
}
