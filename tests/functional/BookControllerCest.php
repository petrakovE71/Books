<?php

declare(strict_types=1);

namespace tests\functional;

use app\models\User;
use app\models\Book;
use tests\fixtures\UserFixture;
use tests\fixtures\BookFixture;
use tests\fixtures\AuthorFixture;
use FunctionalTester;

class BookControllerCest
{
    public function _fixtures(): array
    {
        return [
            'users' => UserFixture::class,
            'books' => BookFixture::class,
            'authors' => AuthorFixture::class,
        ];
    }

    public function testIndexPageAsGuest(FunctionalTester $I): void
    {
        $I->amOnPage('/book/index');
        $I->seeResponseCodeIs(200);
        $I->see('Books', 'h1');
    }

    public function testIndexPageAsUser(FunctionalTester $I): void
    {
        $I->amLoggedInAs(User::findOne(['username' => 'admin']));
        $I->amOnPage('/book/index');
        $I->seeResponseCodeIs(200);
        $I->see('Books', 'h1');
    }

    public function testViewBookAsGuest(FunctionalTester $I): void
    {
        $I->amOnPage('/book/view?id=1');
        $I->seeResponseCodeIs(200);
    }

    public function testViewBookAsUser(FunctionalTester $I): void
    {
        $I->amLoggedInAs(User::findOne(['username' => 'admin']));
        $I->amOnPage('/book/view?id=1');
        $I->seeResponseCodeIs(200);
    }

    public function testViewNonExistentBook(FunctionalTester $I): void
    {
        $I->amOnPage('/book/view?id=99999');
        $I->seeResponseCodeIs(404);
    }

    public function testCreatePageAsGuest(FunctionalTester $I): void
    {
        $I->amOnPage('/book/create');
        $I->seeResponseCodeIs(302);
        $I->seeInCurrentUrl('/site/login');
    }

    public function testCreatePageAsUser(FunctionalTester $I): void
    {
        $user = User::findOne(['username' => 'admin']);
        $I->amLoggedInAs($user);

        \Yii::$app->authManager->assign(
            \Yii::$app->authManager->getPermission('createBook'),
            $user->id
        );

        $I->amOnPage('/book/create');
        $I->seeResponseCodeIs(200);
        $I->see('Create Book', 'h1');
    }

    public function testCreateBookSuccess(FunctionalTester $I): void
    {
        $user = User::findOne(['username' => 'admin']);
        $I->amLoggedInAs($user);

        \Yii::$app->authManager->assign(
            \Yii::$app->authManager->getPermission('createBook'),
            $user->id
        );

        $I->amOnPage('/book/create');

        $I->submitForm('#book-form', [
            'CreateBookDto[title]' => 'Functional Test Book',
            'CreateBookDto[year]' => 2024,
            'CreateBookDto[isbn]' => '978-3-16-148410-9',
            'CreateBookDto[authorIds]' => [1],
            'CreateBookDto[description]' => 'Test description',
        ]);

        $I->seeResponseCodeIs(302);

        $book = Book::findOne(['isbn' => '978-3-16-148410-9']);
        $I->assertNotNull($book);
        $I->assertEquals('Functional Test Book', $book->title);
    }

    public function testUpdatePageAsGuest(FunctionalTester $I): void
    {
        $I->amOnPage('/book/update?id=1');
        $I->seeResponseCodeIs(302);
    }

    public function testUpdatePageAsUser(FunctionalTester $I): void
    {
        $user = User::findOne(['username' => 'admin']);
        $I->amLoggedInAs($user);

        \Yii::$app->authManager->assign(
            \Yii::$app->authManager->getPermission('updateBook'),
            $user->id
        );

        $I->amOnPage('/book/update?id=1');
        $I->seeResponseCodeIs(200);
        $I->see('Update Book', 'h1');
    }

    public function testDeleteAsGuest(FunctionalTester $I): void
    {
        $I->sendAjaxPostRequest('/book/delete?id=1');
        $I->seeResponseCodeIs(302);
    }

    public function testDeleteAsUser(FunctionalTester $I): void
    {
        $user = User::findOne(['username' => 'admin']);
        $I->amLoggedInAs($user);

        \Yii::$app->authManager->assign(
            \Yii::$app->authManager->getPermission('deleteBook'),
            $user->id
        );

        $book = Book::findOne(1);
        $this->assertNull($book->deleted_at);

        $I->sendAjaxPostRequest('/book/delete?id=1');
        $I->seeResponseCodeIs(302);

        $book->refresh();
        $I->assertNotNull($book->deleted_at);
    }
}
