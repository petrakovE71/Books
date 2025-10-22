<?php

declare(strict_types=1);

namespace tests\unit\services;

use app\common\services\BookService;
use app\common\repositories\BookRepository;
use app\common\dto\CreateBookDto;
use app\common\exceptions\BookNotFoundException;
use app\common\exceptions\ValidationException;
use app\models\Book;
use tests\fixtures\BookFixture;
use tests\fixtures\AuthorFixture;
use Codeception\Test\Unit;

class BookServiceTest extends Unit
{
    private BookService $service;

    public function _fixtures(): array
    {
        return [
            'books' => BookFixture::class,
            'authors' => AuthorFixture::class,
        ];
    }

    protected function _before(): void
    {
        parent::_before();

        $repository = new BookRepository();
        $this->service = new BookService($repository);
    }

    public function testCreateBook(): void
    {
        $dto = new CreateBookDto(
            title: 'New Test Book',
            year: 2024,
            isbn: '978-3-16-148410-9',
            authorIds: [1, 2],
            description: 'Test description'
        );

        $book = $this->service->createBook($dto);

        $this->assertInstanceOf(Book::class, $book);
        $this->assertNotNull($book->id);
        $this->assertEquals('New Test Book', $book->title);
        $this->assertEquals(2024, $book->year);
        $this->assertEquals('978-3-16-148410-9', $book->isbn);
    }

    public function testCreateBookWithInvalidData(): void
    {
        $dto = new CreateBookDto(
            title: '',
            year: 2024,
            isbn: 'invalid',
            authorIds: []
        );

        $this->expectException(ValidationException::class);

        $this->service->createBook($dto);
    }

    public function testUpdateBook(): void
    {
        $book = $this->tester->grabFixture('books', 'book1');

        $dto = new CreateBookDto(
            title: 'Updated Title',
            year: 2025,
            isbn: '978-3-16-148410-0',
            authorIds: [1]
        );

        $updatedBook = $this->service->updateBook($book->id, $dto);

        $this->assertEquals('Updated Title', $updatedBook->title);
        $this->assertEquals(2025, $updatedBook->year);
    }

    public function testUpdateNonExistentBook(): void
    {
        $dto = new CreateBookDto(
            title: 'Test',
            year: 2024,
            isbn: '978-3-16-148410-8',
            authorIds: [1]
        );

        $this->expectException(BookNotFoundException::class);

        $this->service->updateBook(99999, $dto);
    }

    public function testDeleteBook(): void
    {
        $book = $this->tester->grabFixture('books', 'book1');

        $result = $this->service->deleteBook($book->id);

        $this->assertTrue($result);

        $book->refresh();
        $this->assertNotNull($book->deleted_at);
    }

    public function testDeleteNonExistentBook(): void
    {
        $this->expectException(BookNotFoundException::class);

        $this->service->deleteBook(99999);
    }

    public function testTransactionRollbackOnError(): void
    {
        $dto = new CreateBookDto(
            title: 'Test Book',
            year: 999,
            isbn: '978-3-16-148410-7',
            authorIds: [1]
        );

        try {
            $this->service->createBook($dto);
            $this->fail('Should throw ValidationException');
        } catch (ValidationException $e) {
            $book = Book::findOne(['isbn' => '978-3-16-148410-7']);
            $this->assertNull($book, 'Book should not be saved due to transaction rollback');
        }
    }
}
