<?php

declare(strict_types=1);

namespace app\common\dto;

final readonly class CreateSubscriptionDto
{
    public function __construct(
        public int $authorId,
        public string $name,
        public string $phone,
    ) {}

    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new self(
            authorId: (int)$data['author_id'],
            name: $data['name'],
            phone: $data['phone']
        );
    }
}
