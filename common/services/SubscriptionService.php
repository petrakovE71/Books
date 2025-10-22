<?php

declare(strict_types=1);

namespace app\common\services;

use Yii;
use app\models\Subscription;
use app\common\dto\CreateSubscriptionDto;
use app\common\repositories\SubscriptionRepository;
use app\common\exceptions\ValidationException;

final class SubscriptionService
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
    ) {}

    /**
     * @param CreateSubscriptionDto $dto
     * @return Subscription
     * @throws ValidationException
     */
    public function createSubscription(CreateSubscriptionDto $dto): Subscription
    {
        if ($this->subscriptionRepository->exists($dto->phone, $dto->authorId)) {
            throw new ValidationException('Subscription already exists');
        }

        $subscription = new Subscription();
        $subscription->author_id = $dto->authorId;
        $subscription->name = $dto->name;
        $subscription->phone = $dto->phone;

        if (!$subscription->save()) {
            throw new ValidationException('Subscription validation failed: ' . json_encode($subscription->errors));
        }

        Yii::info(
            "Subscription created: #{$subscription->id} phone={$subscription->phone} author_id={$subscription->author_id}",
            __METHOD__
        );

        return $subscription;
    }
}
