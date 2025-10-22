<?php

declare(strict_types=1);

namespace app\common\repositories;

use app\models\Book;
use app\models\Author;
use yii\db\ActiveQuery;
use yii\db\Query;

class BookRepository
{
    /**
     * @param int $year
     * @return array
     */
    public function getTop10AuthorsByYear(int $year): array
    {
        $query = (new Query())
            ->select([
                'a.id as author_id',
                'a.fio as author_fio',
                'COUNT(DISTINCT b.id) as books_count'
            ])
            ->from(['a' => '{{%author}}'])
            ->innerJoin(['ba' => '{{%book_author}}'], 'ba.author_id = a.id')
            ->innerJoin(['b' => '{{%book}}'], 'b.id = ba.book_id')
            ->where([
                'b.year' => $year,
                'b.deleted_at' => null,
                'a.deleted_at' => null,
            ])
            ->groupBy(['a.id', 'a.fio'])
            ->orderBy(['books_count' => SORT_DESC, 'a.fio' => SORT_ASC])
            ->limit(10);

        return $query->all();
    }

    /**
     * @param int $id
     * @return Book|null
     */
    public function findByIdWithAuthors(int $id): ?Book
    {
        return Book::find()
            ->with('authors')
            ->where(['id' => $id])
            ->one();
    }

    /**
     * @return ActiveQuery
     */
    public function findAllWithAuthors(): ActiveQuery
    {
        return Book::find()
            ->with('authors')
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * @param int $authorId
     * @return ActiveQuery
     */
    public function findByAuthorId(int $authorId): ActiveQuery
    {
        return Book::find()
            ->innerJoinWith('authors')
            ->where(['author.id' => $authorId])
            ->orderBy(['book.year' => SORT_DESC, 'book.title' => SORT_ASC]);
    }

    /**
     * @param string $isbn
     * @return Book|null
     */
    public function findByIsbn(string $isbn): ?Book
    {
        return Book::findOne(['isbn' => $isbn]);
    }

    /**
     * @param int|null $startYear
     * @param int|null $endYear
     * @return array
     */
    public function getBooksStatsByYears(?int $startYear = null, ?int $endYear = null): array
    {
        $query = (new Query())
            ->select(['year', 'COUNT(*) as count'])
            ->from('{{%book}}')
            ->where(['deleted_at' => null])
            ->groupBy('year')
            ->orderBy(['year' => SORT_DESC]);

        if ($startYear !== null) {
            $query->andWhere(['>=', 'year', $startYear]);
        }

        if ($endYear !== null) {
            $query->andWhere(['<=', 'year', $endYear]);
        }

        return $query->all();
    }

    /**
     * @param string $title
     * @return ActiveQuery
     */
    public function searchByTitle(string $title): ActiveQuery
    {
        return Book::find()
            ->where(['like', 'title', $title])
            ->orderBy(['title' => SORT_ASC]);
    }

    /**
     * @param int $limit
     * @return array
     */
    public function getLatestBooks(int $limit = 10): array
    {
        return Book::find()
            ->with('authors')
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }
}
