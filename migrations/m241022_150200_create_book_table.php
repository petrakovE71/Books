<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%book}}`.
 */
class m241022_150200_create_book_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%book}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull()->comment('Название книги'),
            'year' => $this->integer()->notNull()->comment('Год выпуска'),
            'description' => $this->text()->null()->comment('Описание книги'),
            'isbn' => $this->string(20)->notNull()->unique()->comment('ISBN номер'),
            'cover_photo' => $this->string(255)->null()->comment('Путь к фото обложки'),
            'created_at' => $this->integer()->notNull()->comment('Дата создания'),
            'updated_at' => $this->integer()->notNull()->comment('Дата обновления'),
            'deleted_at' => $this->integer()->null()->comment('Дата удаления (soft delete)'),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->createIndex(
            'idx-book-isbn',
            '{{%book}}',
            'isbn',
            true // unique
        );

        $this->createIndex(
            'idx-book-year',
            '{{%book}}',
            'year'
        );

        $this->createIndex(
            'idx-book-title',
            '{{%book}}',
            'title'
        );

        $this->createIndex(
            'idx-book-deleted_at',
            '{{%book}}',
            'deleted_at'
        );

        $this->createIndex(
            'idx-book-year-deleted',
            '{{%book}}',
            ['year', 'deleted_at']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%book}}');
    }
}
