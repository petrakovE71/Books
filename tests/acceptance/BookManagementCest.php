<?php

declare(strict_types=1);

namespace tests\acceptance;

use app\models\User;
use app\models\Book;
use tests\fixtures\UserFixture;
use tests\fixtures\BookFixture;
use tests\fixtures\AuthorFixture;
use AcceptanceTester;

class BookManagementCest
{
    public function _fixtures(): array
    {
        return [
            'users' => UserFixture::class,
            'books' => BookFixture::class,
            'authors' => AuthorFixture::class,
        ];
    }

    public function testCompleteBookManagementWorkflow(AcceptanceTester $I): void
    {
        $I->wantTo('perform complete book management workflow');

        $I->amOnPage('/site/login');
        $I->fillField('LoginForm[username]', 'admin');
        $I->fillField('LoginForm[password]', 'admin123');
        $I->click('Login');
        $I->see('Logout');

        $authManager = \Yii::$app->authManager;
        $user = User::findOne(['username' => 'admin']);
        foreach (['createBook', 'updateBook', 'deleteBook'] as $perm) {
            $permission = $authManager->getPermission($perm);
            if ($permission) {
                $authManager->assign($permission, $user->id);
            }
        }

        $I->amOnPage('/book/index');
        $I->click('Create Book');
        $I->seeInCurrentUrl('/book/create');

        $I->fillField('CreateBookDto[title]', 'E2E Test Book');
        $I->fillField('CreateBookDto[year]', '2024');
        $I->fillField('CreateBookDto[isbn]', '978-1-234-56789-0');
        $I->selectOption('CreateBookDto[authorIds][]', '1');
        $I->fillField('CreateBookDto[description]', 'Full workflow test book description');

        $I->click('Create');

        $I->see('Book successfully created');
        $I->see('E2E Test Book');

        $book = Book::findOne(['isbn' => '978-1-234-56789-0']);
        $I->assertNotNull($book);

        $I->amOnPage("/book/view?id={$book->id}");
        $I->see('E2E Test Book');
        $I->see('2024');
        $I->see('Full workflow test book description');

        $I->click('Update');
        $I->seeInCurrentUrl('/book/update');

        $I->fillField('CreateBookDto[title]', 'E2E Test Book Updated');
        $I->fillField('CreateBookDto[description]', 'Updated description');
        $I->click('Update');

        $I->see('Book successfully updated');
        $I->see('E2E Test Book Updated');

        $book->refresh();
        $I->assertEquals('E2E Test Book Updated', $book->title);

        $I->amOnPage("/book/view?id={$book->id}");
        $I->click('Delete');
        $I->acceptPopup();

        $I->see('Book successfully deleted');

        $book->refresh();
        $I->assertNotNull($book->deleted_at);

        $I->click('Logout');
        $I->see('Login');
    }

    public function testGuestCanBrowseBooks(AcceptanceTester $I): void
    {
        $I->wantTo('browse books as a guest');

        $I->amOnPage('/book/index');
        $I->see('Books');
        $I->see('Test Book');

        $I->click('View', '.book-item:first-child');
        $I->see('Book Details');
        $I->see('Authors');

        $I->dontSee('Update');
        $I->dontSee('Delete');
        $I->dontSee('Create Book');
    }

    public function testSearchForBooks(AcceptanceTester $I): void
    {
        $I->wantTo('search for books');

        $I->amOnPage('/book/index');

        if ($I->seeElement('#search-form')) {
            $I->fillField('search', 'Test');
            $I->click('Search');

            $I->see('Test Book');
        }
    }

    public function testViewBookDetails(AcceptanceTester $I): void
    {
        $I->wantTo('view detailed book information');

        $I->amOnPage('/book/view?id=1');

        $I->see('Book Details');
        $I->see('Title');
        $I->see('Year');
        $I->see('ISBN');
        $I->see('Description');
        $I->see('Authors');
    }
}
