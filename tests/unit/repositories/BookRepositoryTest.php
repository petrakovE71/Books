<?php

declare(strict_types=1);

namespace tests\unit\repositories;

use app\common\repositories\BookRepository;
use app\models\Book;
use tests\fixtures\BookFixture;
use tests\fixtures\AuthorFixture;
use Codeception\Test\Unit;

class BookRepositoryTest extends Unit
{
    private BookRepository $repository;

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
        $this->repository = new BookRepository();
    }

    public function testGetTop10AuthorsByYear(): void
    {
        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => 1,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => 2,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        $result = $this->repository->getTop10AuthorsByYear(2023);

        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(10, count($result));

        if (!empty($result)) {
            $this->assertArrayHasKey('author_id', $result[0]);
            $this->assertArrayHasKey('author_fio', $result[0]);
            $this->assertArrayHasKey('books_count', $result[0]);
        }
    }

    public function testGetTop10AuthorsByYearWithNoBooks(): void
    {
        $result = $this->repository->getTop10AuthorsByYear(1900);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testFindByIdWithAuthors(): void
    {
        $book = $this->tester->grabFixture('books', 'book1');

        $result = $this->repository->findByIdWithAuthors($book->id);

        $this->assertInstanceOf(Book::class, $result);
        $this->assertEquals($book->id, $result->id);
    }

    public function testFindByIdWithAuthorsNotFound(): void
    {
        $result = $this->repository->findByIdWithAuthors(99999);

        $this->assertNull($result);
    }

    public function testFindAllWithAuthors(): void
    {
        $query = $this->repository->findAllWithAuthors();

        $this->assertInstanceOf('\yii\db\ActiveQuery', $query);

        $books = $query->all();
        $this->assertIsArray($books);
    }

    public function testFindByAuthorId(): void
    {
        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => 1,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        $query = $this->repository->findByAuthorId(1);

        $this->assertInstanceOf('\yii\db\ActiveQuery', $query);

        $books = $query->all();
        $this->assertIsArray($books);
    }

    public function testFindByIsbn(): void
    {
        $book = $this->tester->grabFixture('books', 'book1');

        $result = $this->repository->findByIsbn($book->isbn);

        $this->assertInstanceOf(Book::class, $result);
        $this->assertEquals($book->id, $result->id);
    }

    public function testFindByIsbnNotFound(): void
    {
        $result = $this->repository->findByIsbn('non-existent-isbn');

        $this->assertNull($result);
    }

    public function testGetBooksStatsByYears(): void
    {
        $stats = $this->repository->getBooksStatsByYears();

        $this->assertIsArray($stats);

        foreach ($stats as $stat) {
            $this->assertArrayHasKey('year', $stat);
            $this->assertArrayHasKey('count', $stat);
        }
    }

    public function testGetBooksStatsByYearsWithRange(): void
    {
        $stats = $this->repository->getBooksStatsByYears(2023, 2024);

        $this->assertIsArray($stats);

        foreach ($stats as $stat) {
            $this->assertGreaterThanOrEqual(2023, $stat['year']);
            $this->assertLessThanOrEqual(2024, $stat['year']);
        }
    }

    public function testSearchByTitle(): void
    {
        $query = $this->repository->searchByTitle('Test');

        $this->assertInstanceOf('\yii\db\ActiveQuery', $query);

        $books = $query->all();
        $this->assertIsArray($books);

        foreach ($books as $book) {
            $this->assertStringContainsStringIgnoringCase('test', $book->title);
        }
    }

    public function testGetLatestBooks(): void
    {
        $books = $this->repository->getLatestBooks(5);

        $this->assertIsArray($books);
        $this->assertLessThanOrEqual(5, count($books));
    }

    public function testGetLatestBooksDefaultLimit(): void
    {
        $books = $this->repository->getLatestBooks();

        $this->assertIsArray($books);
        $this->assertLessThanOrEqual(10, count($books));
    }
}
