<?php

declare(strict_types=1);

namespace tests\unit\models;

use app\models\Author;
use tests\fixtures\AuthorFixture;
use tests\fixtures\BookFixture;
use Codeception\Test\Unit;

class AuthorTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'authors' => AuthorFixture::class,
            'books' => BookFixture::class,
        ];
    }

    public function testValidation(): void
    {
        $author = new Author();

        $this->assertFalse($author->validate());
        $this->assertArrayHasKey('fio', $author->errors);
    }

    public function testValidAuthorCreation(): void
    {
        $author = new Author(['fio' => 'Тестов Тест Тестович']);

        $this->assertTrue($author->validate());
    }

    public function testFioPattern(): void
    {
        $validAuthor = new Author(['fio' => 'Иванов Иван Иванович']);
        $this->assertTrue($validAuthor->validate(['fio']));

        $validAuthorEn = new Author(['fio' => 'John Smith']);
        $this->assertTrue($validAuthorEn->validate(['fio']));

        $invalidAuthor = new Author(['fio' => 'Invalid123']);
        $this->assertFalse($invalidAuthor->validate(['fio']));
        $this->assertArrayHasKey('fio', $invalidAuthor->errors);
    }

    public function testSoftDelete(): void
    {
        $author = $this->tester->grabFixture('authors', 'author1');

        $this->assertNull($author->deleted_at);

        $result = $author->softDelete();

        $this->assertTrue($result);
        $this->assertNotNull($author->deleted_at);
    }

    public function testRestore(): void
    {
        $author = $this->tester->grabFixture('authors', 'deleted_author');

        $this->assertNotNull($author->deleted_at);

        $result = $author->restore();

        $this->assertTrue($result);
        $this->assertNull($author->deleted_at);
    }

    public function testFindOnlyNotDeleted(): void
    {
        $authors = Author::find()->all();

        foreach ($authors as $author) {
            $this->assertNull($author->deleted_at);
        }
    }

    public function testBooksRelation(): void
    {
        $author = $this->tester->grabFixture('authors', 'author1');

        $this->assertInstanceOf('\yii\db\ActiveQuery', $author->getBooks());
    }

    public function testGetBooksCount(): void
    {
        $author = $this->tester->grabFixture('authors', 'author1');

        $count = $author->getBooksCount();

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testGetBooksCountByYear(): void
    {
        $author = $this->tester->grabFixture('authors', 'author1');

        $count = $author->getBooksCountByYear(2023);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
}
