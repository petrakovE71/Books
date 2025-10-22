<?php

declare(strict_types=1);

namespace app\common\dto;

final readonly class NotificationDto
{
    public function __construct(
        public int $subscriptionId,
        public int $bookId,
        public string $phone,
        public string $message,
    ) {}
}
