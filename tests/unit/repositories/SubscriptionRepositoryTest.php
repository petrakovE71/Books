<?php

declare(strict_types=1);

namespace tests\unit\repositories;

use app\common\repositories\SubscriptionRepository;
use app\models\Subscription;
use tests\fixtures\SubscriptionFixture;
use tests\fixtures\AuthorFixture;
use Codeception\Test\Unit;

class SubscriptionRepositoryTest extends Unit
{
    private SubscriptionRepository $repository;

    public function _fixtures(): array
    {
        return [
            'subscriptions' => SubscriptionFixture::class,
            'authors' => AuthorFixture::class,
        ];
    }

    protected function _before(): void
    {
        parent::_before();
        $this->repository = new SubscriptionRepository();
    }

    public function testFindByAuthorId(): void
    {
        $subscriptions = $this->repository->findByAuthorId(1);

        $this->assertIsArray($subscriptions);
        $this->assertNotEmpty($subscriptions);

        foreach ($subscriptions as $subscription) {
            $this->assertInstanceOf(Subscription::class, $subscription);
            $this->assertEquals(1, $subscription->author_id);
        }
    }

    public function testFindByAuthorIdWithNoSubscriptions(): void
    {
        $subscriptions = $this->repository->findByAuthorId(3);

        $this->assertIsArray($subscriptions);
        $this->assertEmpty($subscriptions);
    }

    public function testFindByAuthorIds(): void
    {
        $subscriptions = $this->repository->findByAuthorIds([1, 2]);

        $this->assertIsArray($subscriptions);
        $this->assertNotEmpty($subscriptions);

        foreach ($subscriptions as $subscription) {
            $this->assertContains($subscription->author_id, [1, 2]);
        }
    }

    public function testFindByAuthorIdsWithEmptyArray(): void
    {
        $subscriptions = $this->repository->findByAuthorIds([]);

        $this->assertIsArray($subscriptions);
        $this->assertEmpty($subscriptions);
    }

    public function testFindByPhoneAndAuthor(): void
    {
        $subscription = $this->repository->findByPhoneAndAuthor('+79991234567', 1);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals('+79991234567', $subscription->phone);
        $this->assertEquals(1, $subscription->author_id);
    }

    public function testFindByPhoneAndAuthorNotFound(): void
    {
        $subscription = $this->repository->findByPhoneAndAuthor('+79999999999', 1);

        $this->assertNull($subscription);
    }

    public function testFindByPhone(): void
    {
        $subscriptions = $this->repository->findByPhone('+79991234567');

        $this->assertIsArray($subscriptions);
        $this->assertNotEmpty($subscriptions);

        foreach ($subscriptions as $subscription) {
            $this->assertEquals('+79991234567', $subscription->phone);
            $this->assertNotNull($subscription->author);
        }
    }

    public function testCountByAuthorId(): void
    {
        $count = $this->repository->countByAuthorId(1);

        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }

    public function testCountByAuthorIdWithNoSubscriptions(): void
    {
        $count = $this->repository->countByAuthorId(3);

        $this->assertEquals(0, $count);
    }

    public function testGetSubscriptionsStatsByAuthors(): void
    {
        $stats = $this->repository->getSubscriptionsStatsByAuthors();

        $this->assertIsArray($stats);

        foreach ($stats as $stat) {
            $this->assertArrayHasKey('author_id', $stat);
            $this->assertArrayHasKey('subscribers_count', $stat);
        }
    }

    public function testExists(): void
    {
        $exists = $this->repository->exists('+79991234567', 1);

        $this->assertTrue($exists);
    }

    public function testExistsReturnsFalse(): void
    {
        $exists = $this->repository->exists('+79999999999', 1);

        $this->assertFalse($exists);
    }
}
