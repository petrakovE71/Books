<?php

use yii\db\Migration;

/**
 */
class m241022_150600_init_rbac extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $viewBooks = $auth->createPermission('viewBooks');
        $viewBooks->description = 'Просмотр списка и деталей книг';
        $auth->add($viewBooks);

        $createBook = $auth->createPermission('createBook');
        $createBook->description = 'Создание новой книги';
        $auth->add($createBook);

        $updateBook = $auth->createPermission('updateBook');
        $updateBook->description = 'Редактирование книги';
        $auth->add($updateBook);

        $deleteBook = $auth->createPermission('deleteBook');
        $deleteBook->description = 'Удаление книги';
        $auth->add($deleteBook);

        $viewAuthors = $auth->createPermission('viewAuthors');
        $viewAuthors->description = 'Просмотр списка и деталей авторов';
        $auth->add($viewAuthors);

        $createAuthor = $auth->createPermission('createAuthor');
        $createAuthor->description = 'Создание нового автора';
        $auth->add($createAuthor);

        $updateAuthor = $auth->createPermission('updateAuthor');
        $updateAuthor->description = 'Редактирование автора';
        $auth->add($updateAuthor);

        $deleteAuthor = $auth->createPermission('deleteAuthor');
        $deleteAuthor->description = 'Удаление автора';
        $auth->add($deleteAuthor);

        $subscribe = $auth->createPermission('subscribe');
        $subscribe->description = 'Подписка на новые книги автора';
        $auth->add($subscribe);

        $viewReport = $auth->createPermission('viewReport');
        $viewReport->description = 'Просмотр отчета топ-10 авторов';
        $auth->add($viewReport);

        $user = $auth->createRole('user');
        $user->description = 'Аутентифицированный пользователь с правами CRUD';
        $auth->add($user);

        $auth->addChild($user, $viewBooks);
        $auth->addChild($user, $createBook);
        $auth->addChild($user, $updateBook);
        $auth->addChild($user, $deleteBook);
        $auth->addChild($user, $viewAuthors);
        $auth->addChild($user, $createAuthor);
        $auth->addChild($user, $updateAuthor);
        $auth->addChild($user, $deleteAuthor);
        $auth->addChild($user, $subscribe);
        $auth->addChild($user, $viewReport);

        $auth->assign($user, 1);

        echo "RBAC initialized successfully.\n";
        echo "Role 'user' created with full CRUD permissions.\n";
        echo "Permissions for guests (unauthenticated): viewBooks, viewAuthors, subscribe, viewReport\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        $auth->removeAll();

        echo "RBAC removed.\n";
    }
}
