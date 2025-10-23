<?php

declare(strict_types=1);

namespace tests\acceptance;

use tests\fixtures\BookFixture;
use tests\fixtures\AuthorFixture;
use AcceptanceTester;

class GuestVsUserCest
{
    public function _fixtures(): array
    {
        return [
            'books' => BookFixture::class,
            'authors' => AuthorFixture::class,
        ];
    }

    public function testGuestCanViewPublicPages(AcceptanceTester $I): void
    {
        $I->wantTo('verify guests can access public pages');

        // Home page
        $I->amOnPage('/');
        $I->seeResponseCodeIsSuccessful();

        // Books index
        $I->amOnPage('/book/index');
        $I->seeResponseCodeIsSuccessful();
        $I->see('Books');

        // Authors index
        $I->amOnPage('/author/index');
        $I->seeResponseCodeIsSuccessful();
        $I->see('Authors');

        // Reports
        $I->amOnPage('/report/index');
        $I->seeResponseCodeIsSuccessful();
        $I->see('Top 10 Authors');

        // Subscriptions
        $I->amOnPage('/subscription/create');
        $I->seeResponseCodeIsSuccessful();
    }

    public function testGuestCannotAccessManagementPages(AcceptanceTester $I): void
    {
        $I->wantTo('verify guests cannot access management pages');

        $protectedPages = [
            '/book/create',
            '/book/update?id=1',
            '/author/create',
            '/author/update?id=1',
        ];

        foreach ($protectedPages as $page) {
            $I->amOnPage($page);
            $I->seeInCurrentUrl('/site/login');
        }
    }

    public function testGuestDoesNotSeeManagementButtons(AcceptanceTester $I): void
    {
        $I->wantTo('verify guests do not see management buttons');

        $I->amOnPage('/book/index');
        $I->dontSee('Create Book');

        $book = $I->grabFixture('books', 'book1');
        $I->amOnPage("/book/view?id={$book->id}");
        $I->dontSee('Update');
        $I->dontSee('Delete');

        $I->amOnPage('/author/index');
        $I->dontSee('Create Author');
    }

    public function testAuthenticatedUserSeesManagementButtons(AcceptanceTester $I): void
    {
        $I->wantTo('verify authenticated users see management buttons');

        $I->amOnPage('/site/login');
        $I->fillField('LoginForm[username]', 'admin');
        $I->fillField('LoginForm[password]', 'admin123');
        $I->click('Login');

        // Assign permissions
        $user = \app\models\User::findOne(['username' => 'admin']);
        $authManager = \Yii::$app->authManager;
        $authManager->assign($authManager->getPermission('createBook'), $user->id);
        $authManager->assign($authManager->getPermission('updateBook'), $user->id);
        $authManager->assign($authManager->getPermission('deleteBook'), $user->id);

        $I->amOnPage('/book/index');
        $I->see('Create Book');

        $book = $I->grabFixture('books', 'book1');
        $I->amOnPage("/book/view?id={$book->id}");
        $I->see('Update');
        $I->see('Delete');
    }

    public function testUserWithoutPermissionsCannotManage(AcceptanceTester $I): void
    {
        $I->wantTo('verify users without permissions cannot manage resources');

        // Login as regular user
        $I->amOnPage('/site/login');
        $I->fillField('LoginForm[username]', 'user1');
        $I->fillField('LoginForm[password]', 'user123');
        $I->click('Login');

        // Try to access create page
        $I->amOnPage('/book/create');
        $I->seeResponseCodeIs(403);

        // Try to access update page
        $I->amOnPage('/book/update?id=1');
        $I->seeResponseCodeIs(403);
    }

    public function testGuestCanSubscribeToAuthor(AcceptanceTester $I): void
    {
        $I->wantTo('verify guests can subscribe to authors');

        $author = $I->grabFixture('authors', 'author1');

        $I->amOnPage('/subscription/create');
        $I->fillField('Subscription[author_id]', (string)$author->id);
        $I->fillField('Subscription[name]', 'Guest Subscriber');
        $I->fillField('Subscription[phone]', '+79991234567');
        $I->click('Subscribe');

        $I->see('Subscription created successfully');
    }

    public function testDeleteButtonRequiresConfirmation(AcceptanceTester $I): void
    {
        $I->wantTo('verify delete actions require confirmation');

        $I->amOnPage('/site/login');
        $I->fillField('LoginForm[username]', 'admin');
        $I->fillField('LoginForm[password]', 'admin123');
        $I->click('Login');

        // Assign permission
        $user = \app\models\User::findOne(['username' => 'admin']);
        $authManager = \Yii::$app->authManager;
        $authManager->assign($authManager->getPermission('deleteBook'), $user->id);

        $book = $I->grabFixture('books', 'book1');
        $I->amOnPage("/book/view?id={$book->id}");

        // Click delete but cancel
        $I->click('Delete');
        $I->cancelPopup();

        // Verify book still exists
        $I->amOnPage('/book/index');
        $I->see($book->title);
    }

    public function testReportsAccessibleToAll(AcceptanceTester $I): void
    {
        $I->wantTo('verify reports are accessible to all users');

        // As guest
        $I->amOnPage('/report/index');
        $I->seeResponseCodeIsSuccessful();
        $I->see('Top 10 Authors');

        // As authenticated user
        $I->amOnPage('/site/login');
        $I->fillField('LoginForm[username]', 'user1');
        $I->fillField('LoginForm[password]', 'user123');
        $I->click('Login');

        $I->amOnPage('/report/index');
        $I->seeResponseCodeIsSuccessful();
        $I->see('Top 10 Authors');
    }

    public function testNavigationMenuDifferences(AcceptanceTester $I): void
    {
        $I->wantTo('verify navigation menu differences between guest and user');

        // As guest
        $I->amOnPage('/');
        $I->see('Login');
        $I->dontSee('Logout');

        // As authenticated user
        $I->amOnPage('/site/login');
        $I->fillField('LoginForm[username]', 'admin');
        $I->fillField('LoginForm[password]', 'admin123');
        $I->click('Login');

        $I->see('Logout');
        $I->dontSee('Login');
    }
}
