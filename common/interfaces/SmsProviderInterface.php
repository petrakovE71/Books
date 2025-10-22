<?php

declare(strict_types=1);

namespace app\common\interfaces;

interface SmsProviderInterface
{
    /**
     * @param string $phone
     * @param string $message
     * @return bool
     * @throws \app\common\exceptions\SmsDeliveryException
     */
    public function sendSms(string $phone, string $message): bool;

    /**
     * @return string
     */
    public function getProviderName(): string;

    /**
     * @return bool
     */
    public function isAvailable(): bool;
}
