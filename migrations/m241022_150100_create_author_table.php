<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%author}}`.
 */
class m241022_150100_create_author_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%author}}', [
            'id' => $this->primaryKey(),
            'fio' => $this->string(255)->notNull()->comment('ФИО автора'),
            'created_at' => $this->integer()->notNull()->comment('Дата создания'),
            'updated_at' => $this->integer()->notNull()->comment('Дата обновления'),
            'deleted_at' => $this->integer()->null()->comment('Дата удаления (soft delete)'),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->createIndex(
            'idx-author-fio',
            '{{%author}}',
            'fio'
        );

        $this->createIndex(
            'idx-author-deleted_at',
            '{{%author}}',
            'deleted_at'
        );

        $time = time();
        $this->batchInsert('{{%author}}', ['fio', 'created_at', 'updated_at'], [
            ['Толстой Лев Николаевич', $time, $time],
            ['Достоевский Федор Михайлович', $time, $time],
            ['Пушкин Александр Сергеевич', $time, $time],
            ['Чехов Антон Павлович', $time, $time],
            ['Тургенев Иван Сергеевич', $time, $time],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%author}}');
    }
}
