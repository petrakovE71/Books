<?php

declare(strict_types=1);

namespace tests\functional;

use app\models\Subscription;
use tests\fixtures\AuthorFixture;
use tests\fixtures\SubscriptionFixture;
use FunctionalTester;

class SubscriptionControllerCest
{
    public function _fixtures(): array
    {
        return [
            'authors' => AuthorFixture::class,
            'subscriptions' => SubscriptionFixture::class,
        ];
    }

    public function testCreatePageAsGuest(FunctionalTester $I): void
    {
        $I->amOnPage('/subscription/create');
        $I->seeResponseCodeIs(200);
        $I->see('Subscribe to Author', 'h1');
    }

    public function testCreatePageShowsAuthors(FunctionalTester $I): void
    {
        $I->amOnPage('/subscription/create');
        $I->seeResponseCodeIs(200);
        $I->seeElement('select[name="Subscription[author_id]"]');
    }

    public function testCreateSubscriptionSuccess(FunctionalTester $I): void
    {
        $I->amOnPage('/subscription/create');

        $I->submitForm('#subscription-form', [
            'Subscription[author_id]' => 3,
            'Subscription[name]' => 'New Subscriber',
            'Subscription[phone]' => '+79995551122',
        ]);

        $I->seeResponseCodeIs(302);
        $I->seeInCurrentUrl('/subscription/success');

        $subscription = Subscription::findOne(['phone' => '+79995551122']);
        $I->assertNotNull($subscription);
        $I->assertEquals('New Subscriber', $subscription->name);
        $I->assertEquals(3, $subscription->author_id);
    }

    public function testCreateSubscriptionWithInvalidPhone(FunctionalTester $I): void
    {
        $I->amOnPage('/subscription/create');

        $I->submitForm('#subscription-form', [
            'Subscription[author_id]' => 1,
            'Subscription[name]' => 'Test User',
            'Subscription[phone]' => 'invalid-phone',
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Phone must be in international format');
    }

    public function testCreateDuplicateSubscription(FunctionalTester $I): void
    {
        $I->amOnPage('/subscription/create');

        $I->submitForm('#subscription-form', [
            'Subscription[author_id]' => 1,
            'Subscription[name]' => 'Duplicate',
            'Subscription[phone]' => '+79991234567',
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('already subscribed');
    }

    public function testCreateSubscriptionWithEmptyFields(FunctionalTester $I): void
    {
        $I->amOnPage('/subscription/create');

        $I->submitForm('#subscription-form', [
            'Subscription[author_id]' => '',
            'Subscription[name]' => '',
            'Subscription[phone]' => '',
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('cannot be blank');
    }

    public function testSuccessPageAfterSubscription(FunctionalTester $I): void
    {
        $I->amOnPage('/subscription/success?id=1');
        $I->seeResponseCodeIs(200);
        $I->see('Successfully subscribed');
    }

    public function testPhoneNormalization(FunctionalTester $I): void
    {
        $I->amOnPage('/subscription/create');

        $I->submitForm('#subscription-form', [
            'Subscription[author_id]' => 3,
            'Subscription[name]' => 'Phone Test',
            'Subscription[phone]' => '8 (999) 333-44-55',
        ]);

        $I->seeResponseCodeIs(302);

        $subscription = Subscription::findOne(['name' => 'Phone Test']);
        $I->assertNotNull($subscription);
        $I->assertEquals('+79993334455', $subscription->phone);
    }
}
