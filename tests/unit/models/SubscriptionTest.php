<?php

declare(strict_types=1);

namespace tests\unit\models;

use app\models\Subscription;
use tests\fixtures\SubscriptionFixture;
use tests\fixtures\AuthorFixture;
use Codeception\Test\Unit;

class SubscriptionTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'subscriptions' => SubscriptionFixture::class,
            'authors' => AuthorFixture::class,
        ];
    }

    public function testValidation(): void
    {
        $subscription = new Subscription();

        $this->assertFalse($subscription->validate());
        $this->assertArrayHasKey('author_id', $subscription->errors);
        $this->assertArrayHasKey('name', $subscription->errors);
        $this->assertArrayHasKey('phone', $subscription->errors);
    }

    public function testValidSubscriptionCreation(): void
    {
        $subscription = new Subscription([
            'author_id' => 1,
            'name' => 'Test User',
            'phone' => '+79991112233',
        ]);

        $this->assertTrue($subscription->validate());
    }

    public function testPhoneValidation(): void
    {
        $validSubscription = new Subscription([
            'author_id' => 1,
            'name' => 'Test',
            'phone' => '+79991234567',
        ]);
        $this->assertTrue($validSubscription->validate(['phone']));

        $invalidSubscription = new Subscription([
            'author_id' => 1,
            'name' => 'Test',
            'phone' => 'invalid-phone',
        ]);
        $this->assertFalse($invalidSubscription->validate(['phone']));
    }

    public function testPhoneNormalization(): void
    {
        $subscription = new Subscription([
            'author_id' => 1,
            'name' => 'Test User',
            'phone' => '8 (999) 123-45-67',
        ]);

        $subscription->save(false);

        $this->assertEquals('+79991234567', $subscription->phone);
    }

    public function testUniquenessValidation(): void
    {
        $subscription = new Subscription([
            'author_id' => 1,
            'name' => 'Test User',
            'phone' => '+79991234567',
        ]);

        $this->assertFalse($subscription->validate());
        $this->assertArrayHasKey('phone', $subscription->errors);
    }

    public function testAuthorRelation(): void
    {
        $subscription = $this->tester->grabFixture('subscriptions', 'sub1');

        $this->assertInstanceOf('\yii\db\ActiveQuery', $subscription->getAuthor());

        $author = $subscription->author;
        $this->assertNotNull($author);
        $this->assertEquals(1, $author->id);
    }

    public function testFormattedPhone(): void
    {
        $subscription = $this->tester->grabFixture('subscriptions', 'sub1');

        $formatted = $subscription->getFormattedPhone();

        $this->assertIsString($formatted);
        $this->assertNotEmpty($formatted);
    }
}
