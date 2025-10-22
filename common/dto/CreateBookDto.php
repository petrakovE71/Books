<?php

declare(strict_types=1);

namespace app\common\dto;

final readonly class CreateBookDto
{
    public function __construct(
        public string $title,
        public int $year,
        public string $isbn,
        public array $authorIds,
        public ?string $description = null,
        public ?string $coverPhoto = null,
    ) {}

    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            year: (int)$data['year'],
            isbn: $data['isbn'],
            authorIds: $data['authorIds'] ?? [],
            description: $data['description'] ?? null,
            coverPhoto: $data['coverPhoto'] ?? null
        );
    }
}
