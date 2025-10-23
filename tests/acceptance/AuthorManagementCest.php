<?php

declare(strict_types=1);

namespace tests\acceptance;

use tests\fixtures\AuthorFixture;
use AcceptanceTester;

class AuthorManagementCest
{
    public function _fixtures(): array
    {
        return [
            'authors' => AuthorFixture::class,
        ];
    }

    public function testCompleteAuthorManagementWorkflow(AcceptanceTester $I): void
    {
        $I->wantTo('manage authors through complete CRUD workflow');

        // Login as admin
        $I->amOnPage('/site/login');
        $I->fillField('LoginForm[username]', 'admin');
        $I->fillField('LoginForm[password]', 'admin123');
        $I->click('Login');

        // Navigate to authors list
        $I->amOnPage('/author/index');
        $I->see('Authors');

        // Create new author
        $I->click('Create Author');
        $I->fillField('Author[last_name]', 'Тестов');
        $I->fillField('Author[first_name]', 'Тест');
        $I->fillField('Author[middle_name]', 'Тестович');
        $I->click('Create');

        $I->see('Тестов Т.Т.');

        // View author details
        $I->click('View');
        $I->see('Тестов');
        $I->see('Тест');
        $I->see('Тестович');

        // Update author
        $I->click('Update');
        $I->fillField('Author[last_name]', 'Обновлёнов');
        $I->click('Update');

        $I->see('Обновлёнов Т.Т.');

        // Delete author
        $I->click('Delete');
        $I->acceptPopup();

        $I->dontSee('Обновлёнов Т.Т.');
    }

    public function testGuestCannotManageAuthors(AcceptanceTester $I): void
    {
        $I->wantTo('verify guests cannot manage authors');

        // Try to access author index
        $I->amOnPage('/author/index');
        $I->seeInCurrentUrl('/site/login');

        // Try to access create page
        $I->amOnPage('/author/create');
        $I->seeInCurrentUrl('/site/login');

        // Try to access update page
        $I->amOnPage('/author/update?id=1');
        $I->seeInCurrentUrl('/site/login');
    }

    public function testGuestCanViewAuthors(AcceptanceTester $I): void
    {
        $I->wantTo('verify guests can view author list and details');

        $I->amOnPage('/author/index');
        $I->seeResponseCodeIsSuccessful();
        $I->see('Authors');

        $author = $I->grabFixture('authors', 'author1');
        $I->see($author->last_name);

        $I->amOnPage("/author/view?id={$author->id}");
        $I->seeResponseCodeIsSuccessful();
        $I->see($author->last_name);
        $I->see($author->first_name);
    }

    public function testFioValidation(AcceptanceTester $I): void
    {
        $I->wantTo('verify FIO validation rules');

        $I->amOnPage('/site/login');
        $I->fillField('LoginForm[username]', 'admin');
        $I->fillField('LoginForm[password]', 'admin123');
        $I->click('Login');

        $I->amOnPage('/author/create');

        // Submit with invalid characters
        $I->fillField('Author[last_name]', 'Test123');
        $I->fillField('Author[first_name]', 'Test');
        $I->fillField('Author[middle_name]', 'Test');
        $I->click('Create');

        $I->see('contains invalid characters');

        // Submit with valid Cyrillic
        $I->fillField('Author[last_name]', 'Тестов');
        $I->fillField('Author[first_name]', 'Тест');
        $I->fillField('Author[middle_name]', 'Тестович');
        $I->click('Create');

        $I->see('Тестов Т.Т.');
    }

    public function testSoftDeletedAuthorsNotVisibleToGuests(AcceptanceTester $I): void
    {
        $I->wantTo('verify soft-deleted authors are not visible');

        $deletedAuthor = $I->grabFixture('authors', 'deletedAuthor');

        $I->amOnPage('/author/index');
        $I->dontSee($deletedAuthor->last_name);

        $I->amOnPage("/author/view?id={$deletedAuthor->id}");
        $I->seeResponseCodeIs(404);
    }

    public function testAuthorBooksCount(AcceptanceTester $I): void
    {
        $I->wantTo('verify author books count is displayed');

        $author = $I->grabFixture('authors', 'author1');

        \Yii::$app->db->createCommand()->insert('{{%book}}', [
            'title' => 'Test Book for Author',
            'year' => 2024,
            'isbn' => '978-1-111-11111-9',
            'description' => 'Test',
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();

        $bookId = \Yii::$app->db->getLastInsertID();

        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => $bookId,
            'author_id' => $author->id,
            'created_at' => time(),
        ])->execute();

        $I->amOnPage("/author/view?id={$author->id}");
        $I->see('Books Count');
        $I->see('1');
    }

    public function testSearchAuthors(AcceptanceTester $I): void
    {
        $I->wantTo('search for authors');

        $author = $I->grabFixture('authors', 'author1');

        $I->amOnPage('/author/index');

        if ($I->seeElement('input[name="search"]')) {
            $I->fillField('search', $author->last_name);
            $I->click('Search');

            $I->see($author->last_name);
        }
    }
}
