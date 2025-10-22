<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%subscription}}`.
 */
class m241022_150400_create_subscription_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%subscription}}', [
            'id' => $this->primaryKey(),
            'author_id' => $this->integer()->notNull()->comment('ID автора, на которого подписан гость'),
            'name' => $this->string(255)->notNull()->comment('Имя гостя'),
            'phone' => $this->string(20)->notNull()->comment('Телефон гостя в международном формате'),
            'created_at' => $this->integer()->notNull()->comment('Дата создания подписки'),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->createIndex(
            'idx-subscription-author_id',
            '{{%subscription}}',
            'author_id'
        );

        $this->createIndex(
            'idx-subscription-phone',
            '{{%subscription}}',
            'phone'
        );

        $this->createIndex(
            'idx-subscription-phone-author',
            '{{%subscription}}',
            ['phone', 'author_id'],
            true
        );

        $this->addForeignKey(
            'fk-subscription-author_id',
            '{{%subscription}}',
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
        $this->dropForeignKey('fk-subscription-author_id', '{{%subscription}}');
        $this->dropTable('{{%subscription}}');
    }
}
