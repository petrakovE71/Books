<?php

declare(strict_types=1);

namespace tests\unit\services;

use app\common\services\SubscriptionService;
use app\common\repositories\SubscriptionRepository;
use app\common\dto\CreateSubscriptionDto;
use app\common\exceptions\ValidationException;
use app\models\Subscription;
use tests\fixtures\SubscriptionFixture;
use tests\fixtures\AuthorFixture;
use Codeception\Test\Unit;

class SubscriptionServiceTest extends Unit
{
    private SubscriptionService $service;

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

        $repository = new SubscriptionRepository();
        $this->service = new SubscriptionService($repository);
    }

    public function testCreateSubscription(): void
    {
        $dto = new CreateSubscriptionDto(
            authorId: 1,
            name: 'New Subscriber',
            phone: '+79995556677'
        );

        $subscription = $this->service->createSubscription($dto);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertNotNull($subscription->id);
        $this->assertEquals(1, $subscription->author_id);
        $this->assertEquals('New Subscriber', $subscription->name);
        $this->assertEquals('+79995556677', $subscription->phone);
    }

    public function testCreateDuplicateSubscription(): void
    {
        $dto = new CreateSubscriptionDto(
            authorId: 1,
            name: 'Duplicate',
            phone: '+79991234567'
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('already exists');

        $this->service->createSubscription($dto);
    }

    public function testCreateSubscriptionWithInvalidPhone(): void
    {
        $dto = new CreateSubscriptionDto(
            authorId: 1,
            name: 'Test',
            phone: 'invalid'
        );

        $this->expectException(ValidationException::class);

        $this->service->createSubscription($dto);
    }

    public function testCreateSubscriptionWithNonExistentAuthor(): void
    {
        $dto = new CreateSubscriptionDto(
            authorId: 99999,
            name: 'Test',
            phone: '+79991112233'
        );

        $this->expectException(ValidationException::class);

        $this->service->createSubscription($dto);
    }
}
