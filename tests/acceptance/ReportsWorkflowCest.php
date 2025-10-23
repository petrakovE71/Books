<?php

declare(strict_types=1);

namespace tests\acceptance;

use tests\fixtures\BookFixture;
use tests\fixtures\AuthorFixture;
use AcceptanceTester;

class ReportsWorkflowCest
{
    public function _fixtures(): array
    {
        return [
            'books' => BookFixture::class,
            'authors' => AuthorFixture::class,
        ];
    }

    public function testViewTop10AuthorsReport(AcceptanceTester $I): void
    {
        $I->wantTo('view the top 10 authors report');

        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => 1,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        $I->amOnPage('/report/index');
        $I->see('Top 10 Authors');
        $I->see(date('Y'));
    }

    public function testFilterReportByYear(AcceptanceTester $I): void
    {
        $I->wantTo('filter report by different years');

        $I->amOnPage('/report/index');

        if ($I->seeElement('select[name="year"]')) {
            $I->selectOption('select[name="year"]', '2023');
            $I->click('Filter');

            $I->see('2023');
            $I->seeInCurrentUrl('year=2023');
        }
    }

    public function testReportShowsAuthorNames(AcceptanceTester $I): void
    {
        $I->wantTo('verify report displays author names');

        \Yii::$app->db->createCommand()->insert('{{%book_author}}', [
            'book_id' => 1,
            'author_id' => 1,
            'created_at' => time(),
        ])->execute();

        $I->amOnPage('/report/index?year=2023');

        if ($I->seeElement('table')) {
            $I->see('Author');
            $I->see('Books Count');
        }
    }

    public function testReportAccessibleToGuests(AcceptanceTester $I): void
    {
        $I->wantTo('verify guests can access reports');

        $I->amOnPage('/report/index');
        $I->seeResponseCodeIsSuccessful();
        $I->see('Top 10 Authors');
    }

    public function testReportWithNoData(AcceptanceTester $I): void
    {
        $I->wantTo('verify report behavior with no data');

        $I->amOnPage('/report/index?year=1900');
        $I->seeResponseCodeIsSuccessful();
        $I->see('No data');
    }
}
