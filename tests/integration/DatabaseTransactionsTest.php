<?php

declare(strict_types=1);

namespace tests\integration;

use app\common\services\BookService;
use app\common\repositories\BookRepository;
use app\common\dto\CreateBookDto;
use app\models\Book;
use tests\fixtures\AuthorFixture;
use Codeception\Test\Unit;

class DatabaseTransactionsTest extends Unit
{
    private BookService $bookService;

    public function _fixtures(): array
    {
        return [
            'authors' => AuthorFixture::class,
        ];
    }

    protected function _before(): void
    {
        parent::_before();

        $repository = new BookRepository();
        $this->bookService = new BookService($repository);
    }

    public function testTransactionCommitsOnSuccess(): void
    {
        $dto = new CreateBookDto(
            title: 'Transaction Test Book',
            year: 2024,
            isbn: '978-1-111-11111-1',
            authorIds: [1],
            description: 'Testing transaction commit'
        );

        $book = $this->bookService->createBook($dto);

        $this->assertNotNull($book->id);

        $savedBook = Book::findOne($book->id);
        $this->assertNotNull($savedBook);
        $this->assertEquals('Transaction Test Book', $savedBook->title);

        $authorLinks = \Yii::$app->db->createCommand(
            'SELECT * FROM {{%book_author}} WHERE book_id = :book_id'
        )->bindValue(':book_id', $book->id)->queryAll();

        $this->assertNotEmpty($authorLinks);
    }

    public function testTransactionRollbackOnValidationError(): void
    {
        $dto = new CreateBookDto(
            title: '',
            year: 2024,
            isbn: '978-2-222-22222-2',
            authorIds: [1]
        );

        try {
            $this->bookService->createBook($dto);
            $this->fail('Should throw ValidationException');
        } catch (\app\common\exceptions\ValidationException $e) {
            $book = Book::findOne(['isbn' => '978-2-222-22222-2']);
            $this->assertNull($book, 'Book should not exist due to rollback');
        }
    }

    public function testTransactionRollbackOnDatabaseError(): void
    {
        $dto = new CreateBookDto(
            title: 'Test Book',
            year: 999,
            isbn: '978-3-333-33333-3',
            authorIds: [1]
        );

        try {
            $this->bookService->createBook($dto);
            $this->fail('Should throw exception');
        } catch (\Exception $e) {
            $book = Book::findOne(['isbn' => '978-3-333-33333-3']);
            $this->assertNull($book);
        }
    }

    public function testUpdateTransaction(): void
    {
        $dto = new CreateBookDto(
            title: 'Original Title',
            year: 2024,
            isbn: '978-4-444-44444-4',
            authorIds: [1]
        );

        $book = $this->bookService->createBook($dto);

        $updateDto = new CreateBookDto(
            title: 'Updated Title',
            year: 2025,
            isbn: '978-4-444-44444-4',
            authorIds: [1, 2]
        );

        $updatedBook = $this->bookService->updateBook($book->id, $updateDto);

        $this->assertEquals('Updated Title', $updatedBook->title);
        $this->assertEquals(2025, $updatedBook->year);

        $authorLinks = \Yii::$app->db->createCommand(
            'SELECT * FROM {{%book_author}} WHERE book_id = :book_id'
        )->bindValue(':book_id', $book->id)->queryAll();

        $this->assertCount(2, $authorLinks);
    }

    public function testDeleteTransaction(): void
    {
        $dto = new CreateBookDto(
            title: 'To Delete',
            year: 2024,
            isbn: '978-5-555-55555-5',
            authorIds: [1]
        );

        $book = $this->bookService->createBook($dto);

        $result = $this->bookService->deleteBook($book->id);

        $this->assertTrue($result);

        $book->refresh();
        $this->assertNotNull($book->deleted_at);

        $deletedBook = Book::findOne($book->id);
        $this->assertNull($deletedBook, 'Book should not be found due to soft delete scope');
    }

    public function testConcurrentTransactions(): void
    {
        $dto1 = new CreateBookDto(
            title: 'Concurrent Book 1',
            year: 2024,
            isbn: '978-6-666-66666-1',
            authorIds: [1]
        );

        $dto2 = new CreateBookDto(
            title: 'Concurrent Book 2',
            year: 2024,
            isbn: '978-6-666-66666-2',
            authorIds: [2]
        );

        $book1 = $this->bookService->createBook($dto1);
        $book2 = $this->bookService->createBook($dto2);

        $this->assertNotEquals($book1->id, $book2->id);
        $this->assertNotNull(Book::findOne($book1->id));
        $this->assertNotNull(Book::findOne($book2->id));
    }
}
