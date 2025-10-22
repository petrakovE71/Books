<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user}}`.
 */
class m241022_150000_create_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(255)->notNull()->unique()->comment('Имя пользователя'),
            'auth_key' => $this->string(32)->notNull()->comment('Ключ аутентификации'),
            'password_hash' => $this->string(255)->notNull()->comment('Хеш пароля'),
            'email' => $this->string(255)->notNull()->unique()->comment('Email'),
            'status' => $this->smallInteger()->notNull()->defaultValue(10)->comment('Статус: 10=активен, 0=заблокирован'),
            'created_at' => $this->integer()->notNull()->comment('Дата создания'),
            'updated_at' => $this->integer()->notNull()->comment('Дата обновления'),
        ], 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB');

        $this->createIndex(
            'idx-user-username',
            '{{%user}}',
            'username'
        );

        $this->createIndex(
            'idx-user-email',
            '{{%user}}',
            'email'
        );

        $this->createIndex(
            'idx-user-status',
            '{{%user}}',
            'status'
        );

        $this->insert('{{%user}}', [
            'username' => 'admin',
            'auth_key' => \Yii::$app->security->generateRandomString(),
            'password_hash' => \Yii::$app->security->generatePasswordHash('admin123'),
            'email' => 'admin@example.com',
            'status' => 10,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%user}}');
    }
}
