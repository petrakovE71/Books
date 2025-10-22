<?php

declare(strict_types=1);

namespace app\common\services;

use Yii;
use app\models\Book;
use app\models\Author;
use app\common\dto\CreateBookDto;
use app\common\repositories\BookRepository;
use app\common\exceptions\BookNotFoundException;
use app\common\exceptions\ValidationException;

final class BookService
{
    public function __construct(
        private readonly BookRepository $bookRepository,
    ) {}

    /**
     * @param CreateBookDto $dto
     * @return Book
     * @throws ValidationException
     */
    public function createBook(CreateBookDto $dto): Book
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $book = new Book();
            $book->title = $dto->title;
            $book->year = $dto->year;
            $book->description = $dto->description;
            $book->isbn = $dto->isbn;
            $book->cover_photo = $dto->coverPhoto;

            if (!$book->save()) {
                throw new ValidationException('Book validation failed: ' . json_encode($book->errors));
            }

            if (!empty($dto->authorIds)) {
                $this->attachAuthorsToBook($book, $dto->authorIds);
            }

            $transaction->commit();

            Yii::info("Book created: #{$book->id} '{$book->title}'", __METHOD__);

            return $book;
        } catch (\Throwable $e) {
            $transaction->rollBack();

            Yii::error("Failed to create book: {$e->getMessage()}", __METHOD__);

            throw $e;
        }
    }

    /**
     * @param int $id
     * @param CreateBookDto $dto
     * @return Book
     * @throws BookNotFoundException
     * @throws ValidationException
     */
    public function updateBook(int $id, CreateBookDto $dto): Book
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $book = $this->findBookById($id);

            $book->title = $dto->title;
            $book->year = $dto->year;
            $book->description = $dto->description;
            $book->isbn = $dto->isbn;

            if ($dto->coverPhoto !== null) {
                $book->cover_photo = $dto->coverPhoto;
            }

            if (!$book->save()) {
                throw new ValidationException('Book validation failed: ' . json_encode($book->errors));
            }

            if (!empty($dto->authorIds)) {
                $this->syncAuthorsToBook($book, $dto->authorIds);
            }

            $transaction->commit();

            Yii::info("Book updated: #{$book->id} '{$book->title}'", __METHOD__);

            return $book;
        } catch (\Throwable $e) {
            $transaction->rollBack();

            Yii::error("Failed to update book #{$id}: {$e->getMessage()}", __METHOD__);

            throw $e;
        }
    }

    /**
     * @param int $id
     * @return bool
     * @throws BookNotFoundException
     */
    public function deleteBook(int $id): bool
    {
        $book = $this->findBookById($id);

        if ($book->softDelete()) {
            Yii::info("Book soft deleted: #{$book->id} '{$book->title}'", __METHOD__);
            return true;
        }

        return false;
    }

    /**
     * @param int $id
     * @return Book
     * @throws BookNotFoundException
     */
    private function findBookById(int $id): Book
    {
        $book = Book::findOne($id);

        if ($book === null) {
            throw new BookNotFoundException("Book with ID {$id} not found");
        }

        return $book;
    }

    /**
     * @param Book $book
     * @param array $authorIds
     * @return void
     */
    private function attachAuthorsToBook(Book $book, array $authorIds): void
    {
        foreach ($authorIds as $authorId) {
            Yii::$app->db->createCommand()->insert('{{%book_author}}', [
                'book_id' => $book->id,
                'author_id' => $authorId,
                'created_at' => time(),
            ])->execute();
        }
    }

    /**
     * @param Book $book
     * @param array $authorIds
     * @return void
     */
    private function syncAuthorsToBook(Book $book, array $authorIds): void
    {
        Yii::$app->db->createCommand()
            ->delete('{{%book_author}}', ['book_id' => $book->id])
            ->execute();

        $this->attachAuthorsToBook($book, $authorIds);
    }
}
