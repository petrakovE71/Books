<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;
use yii\behaviors\TimestampBehavior;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;
use app\common\events\BookCreatedEvent;

/**
 * @property int $id
 * @property string $title
 * @property int $year
 * @property string|null $description
 * @property string $isbn
 * @property string|null $cover_photo
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $deleted_at
 *
 * @property Author[] $authors
 * @property NotificationQueue[] $notificationQueue
 */
class Book extends ActiveRecord
{
    /**
     * @var UploadedFile|null Uploaded file instance
     */
    public ?UploadedFile $coverFile = null;

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%book}}';
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
            [['title', 'year', 'isbn'], 'required'],
            [['year'], 'integer'],
            [['year'], 'compare', 'compareValue' => 1000, 'operator' => '>=',
             'message' => 'Year must be no earlier than 1000'],
            [['year'], 'compare', 'compareValue' => date('Y') + 1, 'operator' => '<=',
             'message' => 'Year cannot be in the future'],

            [['description'], 'string'],
            [['title'], 'string', 'max' => 255],
            [['title', 'description'], 'trim'],

            [['isbn'], 'string', 'max' => 20],
            [['isbn'], 'trim'],
            [['isbn'], 'match', 'pattern' => '/^(?:ISBN(?:-1[03])?:?\s*)?(?=[0-9X]{10}$|(?=(?:[0-9]+[-\s]){3})[-\s0-9X]{13}$|97[89][0-9]{10}$|(?=(?:[0-9]+[-\s]){4})[-\s0-9]{17}$)(?:97[89][-\s]?)?[0-9]{1,5}[-\s]?[0-9]+[-\s]?[0-9]+[-\s]?[0-9X]$/i',
             'message' => 'Invalid ISBN format. Use ISBN-10 or ISBN-13'],
            [['isbn'], 'unique', 'message' => 'Book with this ISBN already exists'],

            [['cover_photo'], 'string', 'max' => 255],

            // File upload validation
            [['coverFile'], 'file', 'skipOnEmpty' => true,
             'extensions' => ['png', 'jpg', 'jpeg', 'gif', 'webp'],
             'maxSize' => 1024 * 1024 * 5, // 5MB
             'checkExtensionByMimeType' => true],

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
            'title' => 'Title',
            'year' => 'Year',
            'description' => 'Description',
            'isbn' => 'ISBN',
            'cover_photo' => 'Cover Photo',
            'coverFile' => 'Upload Cover',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'deleted_at' => 'Deleted At',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getAuthors(): ActiveQuery
    {
        return $this->hasMany(Author::class, ['id' => 'author_id'])
            ->viaTable('{{%book_author}}', ['book_id' => 'id'])
            ->where(['author.deleted_at' => null]);
    }

    /**
     * @return ActiveQuery
     */
    public function getNotificationQueue(): ActiveQuery
    {
        return $this->hasMany(NotificationQueue::class, ['book_id' => 'id']);
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

    /**
     * Upload cover photo
     *
     * @return bool
     * @throws \yii\base\Exception
     */
    public function uploadCover(): bool
    {
        if ($this->coverFile === null) {
            return true;
        }

        $uploadPath = Yii::getAlias('@webroot/uploads/books');

        // Create directory if not exists
        if (!is_dir($uploadPath)) {
            FileHelper::createDirectory($uploadPath, 0755, true);
        }

        // Generate unique filename
        $filename = uniqid('book_', true) . '.' . $this->coverFile->extension;
        $filePath = $uploadPath . '/' . $filename;

        // Delete old cover if exists
        if (!empty($this->cover_photo) && file_exists(Yii::getAlias('@webroot') . $this->cover_photo)) {
            @unlink(Yii::getAlias('@webroot') . $this->cover_photo);
        }

        if ($this->coverFile->saveAs($filePath)) {
            $this->cover_photo = '/uploads/books/' . $filename;
            return true;
        }

        return false;
    }

    /**
     * Get full path to cover photo
     *
     * @return string|null
     */
    public function getCoverUrl(): ?string
    {
        return $this->cover_photo ? Yii::getAlias('@web') . $this->cover_photo : null;
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);

        // Trigger event on new book creation
        if ($insert) {
            $event = new BookCreatedEvent();
            $event->book = $this;
            $this->trigger(BookCreatedEvent::EVENT_NAME, $event);
        }
    }
}
