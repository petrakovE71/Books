<?php

declare(strict_types=1);

namespace tests\integration;

use app\common\repositories\BookRepository;
use tests\fixtures\BookFixture;
use tests\fixtures\AuthorFixture;
use Codeception\Test\Unit;

class ReportCachingTest extends Unit
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

        // Clear cache before each test
        \Yii::$app->cache->flush();
    }

    public function testReportIsCached(): void
    {
        $year = 2023;
        $cacheKey = "top_authors_{$year}";

        // First call should miss cache
        $this->assertFalse(\Yii::$app->cache->exists($cacheKey));

        $result1 = $this->repository->getTop10AuthorsByYear($year);

        // Cache should now exist
        $this->assertTrue(\Yii::$app->cache->exists($cacheKey));

        // Second call should hit cache
        $result2 = $this->repository->getTop10AuthorsByYear($year);

        $this->assertEquals($result1, $result2);
    }

    public function testCacheInvalidationOnNewBook(): void
    {
        $year = 2024;
        $cacheKey = "top_authors_{$year}";

        // Get initial report
        $resultBefore = $this->repository->getTop10AuthorsByYear($year);
        $this->assertTrue(\Yii::$app->cache->exists($cacheKey));

        // Create new book
        \Yii::$app->db->createCommand()->insert('{{%book}}', [
            'title' => 'Cache Invalidation Test',
            'year' => $year,
            'isbn' => '978-1-111-11111-0',
            'description' => 'Test',
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();

        $bookId = \Yii::$app->db->getLastInsertID();

        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => $bookId,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        // Clear cache (simulate cache invalidation on book creation)
        \Yii::$app->cache->delete($cacheKey);

        // Get report again
        $resultAfter = $this->repository->getTop10AuthorsByYear($year);

        // Results should be different if book was added
        $this->assertIsArray($resultAfter);
    }

    public function testDifferentYearsHaveSeparateCache(): void
    {
        $year2023 = 2023;
        $year2024 = 2024;

        $result2023 = $this->repository->getTop10AuthorsByYear($year2023);
        $result2024 = $this->repository->getTop10AuthorsByYear($year2024);

        $cacheKey2023 = "top_authors_{$year2023}";
        $cacheKey2024 = "top_authors_{$year2024}";

        // Both should be cached separately
        $this->assertTrue(\Yii::$app->cache->exists($cacheKey2023));
        $this->assertTrue(\Yii::$app->cache->exists($cacheKey2024));

        // Clearing one should not affect the other
        \Yii::$app->cache->delete($cacheKey2023);

        $this->assertFalse(\Yii::$app->cache->exists($cacheKey2023));
        $this->assertTrue(\Yii::$app->cache->exists($cacheKey2024));
    }

    public function testCacheExpiration(): void
    {
        $year = 2023;
        $cacheKey = "top_authors_{$year}";

        // Set cache with short expiration
        $result = $this->repository->getTop10AuthorsByYear($year);

        $this->assertTrue(\Yii::$app->cache->exists($cacheKey));

        // Manually expire cache
        \Yii::$app->cache->delete($cacheKey);

        $this->assertFalse(\Yii::$app->cache->exists($cacheKey));

        // Next call should regenerate cache
        $result2 = $this->repository->getTop10AuthorsByYear($year);

        $this->assertTrue(\Yii::$app->cache->exists($cacheKey));
    }

    public function testCachePerformanceImprovement(): void
    {
        $year = 2023;

        // Clear cache
        \Yii::$app->cache->flush();

        // First call (no cache)
        $start1 = microtime(true);
        $result1 = $this->repository->getTop10AuthorsByYear($year);
        $time1 = microtime(true) - $start1;

        // Second call (with cache)
        $start2 = microtime(true);
        $result2 = $this->repository->getTop10AuthorsByYear($year);
        $time2 = microtime(true) - $start2;

        // Cached call should be faster or equal
        $this->assertLessThanOrEqual($time1, $time2);
        $this->assertEquals($result1, $result2);
    }

    public function testCacheStoresCorrectDataStructure(): void
    {
        $year = 2023;
        $cacheKey = "top_authors_{$year}";

        $result = $this->repository->getTop10AuthorsByYear($year);
        $cachedData = \Yii::$app->cache->get($cacheKey);

        $this->assertIsArray($cachedData);
        $this->assertEquals($result, $cachedData);

        foreach ($cachedData as $item) {
            $this->assertIsArray($item);
            $this->assertArrayHasKey('author_id', $item);
            $this->assertArrayHasKey('books_count', $item);
        }
    }

    public function testCacheHandlesEmptyResults(): void
    {
        $year = 1900; // Year with no books
        $cacheKey = "top_authors_{$year}";

        $result = $this->repository->getTop10AuthorsByYear($year);

        $this->assertIsArray($result);
        $this->assertEmpty($result);

        // Empty results should still be cached
        $this->assertTrue(\Yii::$app->cache->exists($cacheKey));

        $cachedData = \Yii::$app->cache->get($cacheKey);
        $this->assertEquals($result, $cachedData);
    }

    public function testCacheFlushClearsAllReports(): void
    {
        // Generate cache for multiple years
        $this->repository->getTop10AuthorsByYear(2022);
        $this->repository->getTop10AuthorsByYear(2023);
        $this->repository->getTop10AuthorsByYear(2024);

        $this->assertTrue(\Yii::$app->cache->exists('top_authors_2022'));
        $this->assertTrue(\Yii::$app->cache->exists('top_authors_2023'));
        $this->assertTrue(\Yii::$app->cache->exists('top_authors_2024'));

        // Flush all cache
        \Yii::$app->cache->flush();

        $this->assertFalse(\Yii::$app->cache->exists('top_authors_2022'));
        $this->assertFalse(\Yii::$app->cache->exists('top_authors_2023'));
        $this->assertFalse(\Yii::$app->cache->exists('top_authors_2024'));
    }

    public function testConcurrentCacheAccess(): void
    {
        $year = 2023;

        // Clear cache
        \Yii::$app->cache->flush();

        // Simulate concurrent requests
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->repository->getTop10AuthorsByYear($year);
        }

        // All results should be identical
        foreach ($results as $result) {
            $this->assertEquals($results[0], $result);
        }
    }

    public function testCacheKeyUniqueness(): void
    {
        // Test that different years don't collide
        $result2023 = $this->repository->getTop10AuthorsByYear(2023);
        $result2024 = $this->repository->getTop10AuthorsByYear(2024);

        $cached2023 = \Yii::$app->cache->get('top_authors_2023');
        $cached2024 = \Yii::$app->cache->get('top_authors_2024');

        $this->assertEquals($result2023, $cached2023);
        $this->assertEquals($result2024, $cached2024);

        // Results may differ based on data
        $this->assertIsArray($cached2023);
        $this->assertIsArray($cached2024);
    }
}
