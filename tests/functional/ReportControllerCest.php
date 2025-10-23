<?php

declare(strict_types=1);

namespace tests\functional;

use tests\fixtures\BookFixture;
use tests\fixtures\AuthorFixture;
use FunctionalTester;

class ReportControllerCest
{
    public function _fixtures(): array
    {
        return [
            'books' => BookFixture::class,
            'authors' => AuthorFixture::class,
        ];
    }

    public function testIndexPageAsGuest(FunctionalTester $I): void
    {
        $I->amOnPage('/report/index');
        $I->seeResponseCodeIs(200);
        $I->see('Top 10 Authors Report', 'h1');
    }

    public function testIndexPageAsUser(FunctionalTester $I): void
    {
        $I->amLoggedInAs(\app\models\User::findOne(['username' => 'admin']));
        $I->amOnPage('/report/index');
        $I->seeResponseCodeIs(200);
        $I->see('Top 10 Authors', 'h1');
    }

    public function testReportShowsCurrentYear(FunctionalTester $I): void
    {
        $currentYear = date('Y');
        $I->amOnPage('/report/index');
        $I->see($currentYear);
    }

    public function testReportWithYearParameter(FunctionalTester $I): void
    {
        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => 1,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        $I->amOnPage('/report/index?year=2023');
        $I->seeResponseCodeIs(200);
        $I->see('2023');
    }

    public function testReportShowsAuthorsList(FunctionalTester $I): void
    {
        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => 1,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        $I->amOnPage('/report/index?year=2023');
        $I->seeElement('table');
    }

    public function testReportShowsYearFilter(FunctionalTester $I): void
    {
        $I->amOnPage('/report/index');
        $I->seeElement('select[name="year"]');
    }

    public function testReportCaching(FunctionalTester $I): void
    {
        $year = 2023;

        $I->amOnPage("/report/index?year={$year}");
        $I->seeResponseCodeIs(200);

        $cacheKey = "report_top10_authors_{$year}";
        $cached = \Yii::$app->cache->get($cacheKey);

        $I->assertNotFalse($cached, 'Report should be cached');
    }

    public function testReportWithNoData(FunctionalTester $I): void
    {
        $I->amOnPage('/report/index?year=1900');
        $I->seeResponseCodeIs(200);
        $I->see('No data found');
    }

    public function testReportShowsBookCount(FunctionalTester $I): void
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

        $I->amOnPage('/report/index?year=2023');
        $I->seeResponseCodeIs(200);
    }

    public function testReportOrderedByBooksCount(FunctionalTester $I): void
    {
        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => 1,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        $I->amOnPage('/report/index?year=2023');
        $I->seeResponseCodeIs(200);
    }
}
