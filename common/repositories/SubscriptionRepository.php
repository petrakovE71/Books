<?php

declare(strict_types=1);

namespace app\common\repositories;

use app\models\Subscription;
use yii\db\ActiveQuery;

class SubscriptionRepository
{
    /**
     * @param int $authorId
     * @return array|Subscription[]
     */
    public function findByAuthorId(int $authorId): array
    {
        return Subscription::find()
            ->where(['author_id' => $authorId])
            ->all();
    }

    /**
     * @param array $authorIds
     * @return array|Subscription[]
     */
    public function findByAuthorIds(array $authorIds): array
    {
        if (empty($authorIds)) {
            return [];
        }

        return Subscription::find()
            ->where(['in', 'author_id', $authorIds])
            ->all();
    }

    /**
     * @param string $phone
     * @param int $authorId
     * @return Subscription|null
     */
    public function findByPhoneAndAuthor(string $phone, int $authorId): ?Subscription
    {
        return Subscription::findOne([
            'phone' => $phone,
            'author_id' => $authorId,
        ]);
    }

    /**
     * @param string $phone
     * @return array|Subscription[]
     */
    public function findByPhone(string $phone): array
    {
        return Subscription::find()
            ->with('author')
            ->where(['phone' => $phone])
            ->all();
    }

    /**
     * @param int $authorId
     * @return int
     */
    public function countByAuthorId(int $authorId): int
    {
        return Subscription::find()
            ->where(['author_id' => $authorId])
            ->count();
    }

    /**
     * @return array
     */
    public function getSubscriptionsStatsByAuthors(): array
    {
        return Subscription::find()
            ->select(['author_id', 'COUNT(*) as subscribers_count'])
            ->joinWith('author')
            ->groupBy('author_id')
            ->orderBy(['subscribers_count' => SORT_DESC])
            ->asArray()
            ->all();
    }

    /**
     * @param string $phone
     * @param int $authorId
     * @return bool
     */
    public function exists(string $phone, int $authorId): bool
    {
        return Subscription::find()
            ->where(['phone' => $phone, 'author_id' => $authorId])
            ->exists();
    }
}
