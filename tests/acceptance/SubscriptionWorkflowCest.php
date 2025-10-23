<?php

declare(strict_types=1);

namespace tests\acceptance;

use app\models\Subscription;
use tests\fixtures\AuthorFixture;
use AcceptanceTester;

class SubscriptionWorkflowCest
{
    public function _fixtures(): array
    {
        return [
            'authors' => AuthorFixture::class,
        ];
    }

    public function testGuestCanSubscribeToAuthor(AcceptanceTester $I): void
    {
        $I->wantTo('subscribe to an author as a guest');

        $I->amOnPage('/subscription/create');
        $I->see('Subscribe to Author');

        $I->selectOption('Subscription[author_id]', '1');
        $I->fillField('Subscription[name]', 'John Doe');
        $I->fillField('Subscription[phone]', '+79991234567');

        $I->click('Subscribe');

        $I->see('Successfully subscribed');
        $I->seeInCurrentUrl('/subscription/success');

        $subscription = Subscription::findOne(['phone' => '+79991234567', 'author_id' => 1]);
        $I->assertNotNull($subscription);
        $I->assertEquals('John Doe', $subscription->name);
    }

    public function testPhoneNumberFormatting(AcceptanceTester $I): void
    {
        $I->wantTo('test phone number formatting');

        $I->amOnPage('/subscription/create');

        $I->selectOption('Subscription[author_id]', '2');
        $I->fillField('Subscription[name]', 'Jane Smith');
        $I->fillField('Subscription[phone]', '8 (999) 777-88-99');

        $I->click('Subscribe');

        $I->see('Successfully subscribed');

        $subscription = Subscription::findOne(['name' => 'Jane Smith']);
        $I->assertNotNull($subscription);
        $I->assertEquals('+79997778899', $subscription->phone);
    }

    public function testCannotSubscribeTwice(AcceptanceTester $I): void
    {
        $I->wantTo('verify duplicate subscription prevention');

        $I->amOnPage('/subscription/create');

        $I->selectOption('Subscription[author_id]', '1');
        $I->fillField('Subscription[name]', 'Test User');
        $I->fillField('Subscription[phone]', '+79995554433');
        $I->click('Subscribe');

        $I->see('Successfully subscribed');

        $I->amOnPage('/subscription/create');

        $I->selectOption('Subscription[author_id]', '1');
        $I->fillField('Subscription[name]', 'Test User');
        $I->fillField('Subscription[phone]', '+79995554433');
        $I->click('Subscribe');

        $I->see('already subscribed');
    }

    public function testInvalidPhoneValidation(AcceptanceTester $I): void
    {
        $I->wantTo('test invalid phone number validation');

        $I->amOnPage('/subscription/create');

        $I->selectOption('Subscription[author_id]', '1');
        $I->fillField('Subscription[name]', 'Invalid Phone User');
        $I->fillField('Subscription[phone]', 'not-a-phone');

        $I->click('Subscribe');

        $I->see('Phone must be in international format');
        $I->seeInCurrentUrl('/subscription/create');
    }

    public function testSuccessPageShowsDetails(AcceptanceTester $I): void
    {
        $I->wantTo('verify success page shows subscription details');

        $I->amOnPage('/subscription/create');

        $I->selectOption('Subscription[author_id]', '3');
        $I->fillField('Subscription[name]', 'Success Test');
        $I->fillField('Subscription[phone]', '+79996665544');

        $I->click('Subscribe');

        $I->see('Successfully subscribed');
        $I->see('Success Test');
        $I->see('author');
    }
}
