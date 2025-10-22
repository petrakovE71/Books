<?php

declare(strict_types=1);

namespace tests\unit\models;

use app\models\Book;
use app\models\Author;
use tests\fixtures\BookFixture;
use tests\fixtures\AuthorFixture;
use Codeception\Test\Unit;

class BookTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'books' => BookFixture::class,
            'authors' => AuthorFixture::class,
        ];
    }

    public function testValidation(): void
    {
        $book = new Book();

        $this->assertFalse($book->validate());
        $this->assertArrayHasKey('title', $book->errors);
        $this->assertArrayHasKey('year', $book->errors);
        $this->assertArrayHasKey('isbn', $book->errors);
    }

    public function testValidBookCreation(): void
    {
        $book = new Book([
            'title' => 'Valid Book Title',
            'year' => 2024,
            'isbn' => '978-3-16-148410-3',
            'description' => 'Test description',
        ]);

        $this->assertTrue($book->validate());
    }

    public function testYearValidation(): void
    {
        $book = new Book([
            'title' => 'Test Book',
            'year' => 999,
            'isbn' => '978-3-16-148410-4',
        ]);

        $this->assertFalse($book->validate(['year']));
        $this->assertArrayHasKey('year', $book->errors);
    }

    public function testFutureYearValidation(): void
    {
        $book = new Book([
            'title' => 'Test Book',
            'year' => (int)date('Y') + 2,
            'isbn' => '978-3-16-148410-5',
        ]);

        $this->assertFalse($book->validate(['year']));
        $this->assertArrayHasKey('year', $book->errors);
    }

    public function testIsbnUniqueness(): void
    {
        $book1 = new Book([
            'title' => 'Book 1',
            'year' => 2024,
            'isbn' => '978-3-16-148410-0',
        ]);

        $this->assertFalse($book1->validate(['isbn']));
        $this->assertArrayHasKey('isbn', $book1->errors);
    }

    public function testSoftDelete(): void
    {
        $book = $this->tester->grabFixture('books', 'book1');

        $this->assertNull($book->deleted_at);

        $result = $book->softDelete();

        $this->assertTrue($result);
        $this->assertNotNull($book->deleted_at);
    }

    public function testRestore(): void
    {
        $book = $this->tester->grabFixture('books', 'deleted_book');

        $this->assertNotNull($book->deleted_at);

        $result = $book->restore();

        $this->assertTrue($result);
        $this->assertNull($book->deleted_at);
    }

    public function testFindOnlyNotDeleted(): void
    {
        $books = Book::find()->all();

        foreach ($books as $book) {
            $this->assertNull($book->deleted_at, "Book {$book->id} should not be deleted");
        }
    }

    public function testAuthorsRelation(): void
    {
        $book = $this->tester->grabFixture('books', 'book1');

        $this->assertInstanceOf('\yii\db\ActiveQuery', $book->getAuthors());

        $authors = $book->authors;
        $this->assertIsArray($authors);
    }
}
