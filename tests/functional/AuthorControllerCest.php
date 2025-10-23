<?php

declare(strict_types=1);

namespace tests\functional;

use app\models\User;
use app\models\Author;
use tests\fixtures\UserFixture;
use tests\fixtures\AuthorFixture;
use FunctionalTester;

class AuthorControllerCest
{
    public function _fixtures(): array
    {
        return [
            'users' => UserFixture::class,
            'authors' => AuthorFixture::class,
        ];
    }

    public function testIndexPageAsGuest(FunctionalTester $I): void
    {
        $I->amOnPage('/author/index');
        $I->seeResponseCodeIs(200);
        $I->see('Authors', 'h1');
    }

    public function testIndexPageAsUser(FunctionalTester $I): void
    {
        $I->amLoggedInAs(User::findOne(['username' => 'admin']));
        $I->amOnPage('/author/index');
        $I->seeResponseCodeIs(200);
    }

    public function testViewAuthorAsGuest(FunctionalTester $I): void
    {
        $I->amOnPage('/author/view?id=1');
        $I->seeResponseCodeIs(200);
    }

    public function testViewAuthorWithBooks(FunctionalTester $I): void
    {
        $I->amOnPage('/author/view?id=1');
        $I->seeResponseCodeIs(200);
        $I->see('Books by this author');
    }

    public function testViewNonExistentAuthor(FunctionalTester $I): void
    {
        $I->amOnPage('/author/view?id=99999');
        $I->seeResponseCodeIs(404);
    }

    public function testCreatePageAsGuest(FunctionalTester $I): void
    {
        $I->amOnPage('/author/create');
        $I->seeResponseCodeIs(302);
        $I->seeInCurrentUrl('/site/login');
    }

    public function testCreatePageAsUser(FunctionalTester $I): void
    {
        $user = User::findOne(['username' => 'admin']);
        $I->amLoggedInAs($user);

        \Yii::$app->authManager->assign(
            \Yii::$app->authManager->getPermission('createAuthor'),
            $user->id
        );

        $I->amOnPage('/author/create');
        $I->seeResponseCodeIs(200);
        $I->see('Create Author', 'h1');
    }

    public function testCreateAuthorSuccess(FunctionalTester $I): void
    {
        $user = User::findOne(['username' => 'admin']);
        $I->amLoggedInAs($user);

        \Yii::$app->authManager->assign(
            \Yii::$app->authManager->getPermission('createAuthor'),
            $user->id
        );

        $I->amOnPage('/author/create');

        $I->submitForm('#author-form', [
            'Author[fio]' => 'Тестовый Автор Тестович',
        ]);

        $I->seeResponseCodeIs(302);

        $author = Author::findOne(['fio' => 'Тестовый Автор Тестович']);
        $I->assertNotNull($author);
    }

    public function testCreateAuthorWithInvalidFio(FunctionalTester $I): void
    {
        $user = User::findOne(['username' => 'admin']);
        $I->amLoggedInAs($user);

        \Yii::$app->authManager->assign(
            \Yii::$app->authManager->getPermission('createAuthor'),
            $user->id
        );

        $I->amOnPage('/author/create');

        $I->submitForm('#author-form', [
            'Author[fio]' => 'Invalid123',
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('FIO must contain only letters');
    }

    public function testUpdatePageAsGuest(FunctionalTester $I): void
    {
        $I->amOnPage('/author/update?id=1');
        $I->seeResponseCodeIs(302);
    }

    public function testUpdatePageAsUser(FunctionalTester $I): void
    {
        $user = User::findOne(['username' => 'admin']);
        $I->amLoggedInAs($user);

        \Yii::$app->authManager->assign(
            \Yii::$app->authManager->getPermission('updateAuthor'),
            $user->id
        );

        $I->amOnPage('/author/update?id=1');
        $I->seeResponseCodeIs(200);
        $I->see('Update Author', 'h1');
    }

    public function testUpdateAuthorSuccess(FunctionalTester $I): void
    {
        $user = User::findOne(['username' => 'admin']);
        $I->amLoggedInAs($user);

        \Yii::$app->authManager->assign(
            \Yii::$app->authManager->getPermission('updateAuthor'),
            $user->id
        );

        $I->amOnPage('/author/update?id=1');

        $I->submitForm('#author-form', [
            'Author[fio]' => 'Обновленный Автор',
        ]);

        $I->seeResponseCodeIs(302);

        $author = Author::findOne(1);
        $I->assertEquals('Обновленный Автор', $author->fio);
    }

    public function testDeleteAsGuest(FunctionalTester $I): void
    {
        $I->sendAjaxPostRequest('/author/delete?id=1');
        $I->seeResponseCodeIs(302);
    }

    public function testDeleteAsUser(FunctionalTester $I): void
    {
        $user = User::findOne(['username' => 'admin']);
        $I->amLoggedInAs($user);

        \Yii::$app->authManager->assign(
            \Yii::$app->authManager->getPermission('deleteAuthor'),
            $user->id
        );

        $author = Author::findOne(1);
        $I->assertNull($author->deleted_at);

        $I->sendAjaxPostRequest('/author/delete?id=1');
        $I->seeResponseCodeIs(302);

        $author->refresh();
        $I->assertNotNull($author->deleted_at);
    }
}
