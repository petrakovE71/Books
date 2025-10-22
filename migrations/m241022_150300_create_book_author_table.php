<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%book_author}}`.
 */
class m241022_150300_create_book_author_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%book_author}}', [
            'book_id' => $this->integer()->notNull()->comment('ID книги'),
            'author_id' => $this->integer()->notNull()->comment('ID автора'),
            'created_at' => $this->integer()->notNull()->comment('Дата создания связи'),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->addPrimaryKey(
            'pk-book_author',
            '{{%book_author}}',
            ['book_id', 'author_id']
        );

        $this->createIndex(
            'idx-book_author-author_id',
            '{{%book_author}}',
            'author_id'
        );

        $this->createIndex(
            'idx-book_author-book_id',
            '{{%book_author}}',
            'book_id'
        );

        $this->addForeignKey(
            'fk-book_author-book_id',
            '{{%book_author}}',
            'book_id',
            '{{%book}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-book_author-author_id',
            '{{%book_author}}',
            'author_id',
            '{{%author}}',
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
        $this->dropForeignKey('fk-book_author-book_id', '{{%book_author}}');
        $this->dropForeignKey('fk-book_author-author_id', '{{%book_author}}');

        $this->dropTable('{{%book_author}}');
    }
}
